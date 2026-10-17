<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function createPaymentIntent(
        Donation $donation,
        Campaign $campaign,
        int $amountInCents,
        string $receiptEmail,
    ): PaymentIntent {
        return $this->client()->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => config('services.stripe.currency', 'usd'),
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => $receiptEmail,
            'metadata' => [
                'donation_id' => (string) $donation->id,
                'campaign_id' => (string) $campaign->id,
            ],
        ], [
            'idempotency_key' => 'donation-'.$donation->id,
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! is_string($webhookSecret) || $webhookSecret === '') {
            throw new RuntimeException('The Stripe webhook signing secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signature, $webhookSecret);
    }

    /**
     * @param  array{id: string, amount: int, amount_received: int, currency: string, status: string, metadata: array{donation_id: string, campaign_id: string}}  $paymentIntentData
     */
    public function processWebhookEvent(string $eventId, string $eventType, array $paymentIntentData): void
    {
        if (! in_array($eventType, [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
        ], true)) {
            return;
        }

        $intent = PaymentIntent::constructFrom([
            'object' => 'payment_intent',
            ...$paymentIntentData,
        ]);

        if (! $this->webhookEventMatchesIntent($eventType, $intent)) {
            Log::warning('Queued Stripe webhook type did not match its PaymentIntent status.', [
                'event_id' => $eventId,
                'payment_intent_id' => $intent->id,
                'event_type' => $eventType,
                'payment_intent_status' => $intent->status,
            ]);

            return;
        }

        $donationId = $intent->metadata->donation_id ?? null;
        $donation = is_string($donationId) ? Donation::query()->find($donationId) : null;

        if (! $donation instanceof Donation || ! $this->paymentIntentMatchesDonation($intent, $donation)) {
            Log::warning('Queued Stripe webhook did not match a donation record.', [
                'event_id' => $eventId,
                'payment_intent_id' => $intent->id,
                'donation_id' => $donationId,
            ]);

            return;
        }

        if ($eventType === 'payment_intent.succeeded') {
            if (! $this->markDonationCompleted($donation, $intent)) {
                Log::warning('Queued Stripe payment could not be applied to its donation.', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $intent->id,
                    'donation_id' => $donation->id,
                ]);
            }

            return;
        }

        $this->markDonationFailed($donation, $intent->id);
    }

    public function webhookEventMatchesIntent(string $eventType, PaymentIntent $intent): bool
    {
        return match ($eventType) {
            'payment_intent.succeeded' => $intent->status === 'succeeded',
            'payment_intent.payment_failed' => $intent->status === 'requires_payment_method',
            'payment_intent.canceled' => $intent->status === 'canceled',
            default => false,
        };
    }

    public function amountInCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function paymentIntentMatchesDonation(PaymentIntent $intent, Donation $donation): bool
    {
        $currency = strtolower((string) config('services.stripe.currency', 'usd'));
        $amountInCents = $this->amountInCents((string) $donation->amount);

        return $donation->payment_method === 'Card'
            && (string) ($intent->metadata->donation_id ?? '') === (string) $donation->id
            && (string) ($intent->metadata->campaign_id ?? '') === (string) $donation->campaign_id
            && (int) $intent->amount === $amountInCents
            && ($intent->status !== 'succeeded' || (int) $intent->amount_received === $amountInCents)
            && strtolower((string) $intent->currency) === $currency
            && ($donation->payment_reference === null || hash_equals($donation->payment_reference, $intent->id));
    }

    public function markDonationCompleted(Donation $donation, PaymentIntent $intent): bool
    {
        return DB::transaction(function () use ($donation, $intent): bool {
            $lockedDonation = Donation::query()->lockForUpdate()->findOrFail($donation->id);

            if (
                $lockedDonation->payment_reference !== null
                && ! hash_equals($lockedDonation->payment_reference, $intent->id)
            ) {
                return false;
            }

            if ($lockedDonation->status === 'completed') {
                $this->markCampaignCompletedIfGoalReached($lockedDonation->campaign_id);

                return true;
            }

            $lockedDonation->update([
                'payment_reference' => $intent->id,
                'status' => 'completed',
                'donated_at' => now(),
            ]);
            $this->markCampaignCompletedIfGoalReached($lockedDonation->campaign_id);

            return true;
        });
    }

    private function markCampaignCompletedIfGoalReached(int $campaignId): void
    {
        $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaignId);

        if ($campaign->status === 'completed') {
            return;
        }

        $raisedAmount = (string) Donation::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'completed')
            ->sum('amount');

        if ($this->amountInCents($raisedAmount) >= $this->amountInCents((string) $campaign->goal_amount)) {
            $campaign->update(['status' => 'completed']);
        }
    }

    public function markDonationFailed(Donation $donation, ?string $paymentIntentId = null): void
    {
        DB::transaction(function () use ($donation, $paymentIntentId): void {
            $lockedDonation = Donation::query()->lockForUpdate()->findOrFail($donation->id);

            if (
                in_array($lockedDonation->status, ['completed', 'failed'], true)
                || ($paymentIntentId !== null
                    && $lockedDonation->payment_reference !== null
                    && ! hash_equals($lockedDonation->payment_reference, $paymentIntentId))
            ) {
                return;
            }

            $lockedDonation->update([
                'payment_reference' => $paymentIntentId ?? $lockedDonation->payment_reference,
                'status' => 'failed',
            ]);
        });
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('The Stripe secret key is not configured.');
        }

        return new StripeClient($secret);
    }
}
