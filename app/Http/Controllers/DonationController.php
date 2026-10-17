<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhook;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class DonationController extends Controller
{
    public function storeIntent(
        Request $request,
        Campaign $campaign,
        StripePaymentService $stripe,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $campaign->completeIfExpired();

        $validated = $request->validate([
            'donor_phone' => ['nullable', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.50', 'decimal:0,2', 'max:999999.99'],
        ]);

        if (! $this->campaignAcceptsDonations($campaign)) {
            return response()->json([
                'message' => 'This campaign is not currently accepting donations.',
            ], 422);
        }

        if (! $stripe->isConfigured() || ! filled(config('services.stripe.key'))) {
            return response()->json([
                'message' => 'Card donations are not configured yet. Please contact the site administrator.',
            ], 503);
        }

        $donorEmail = Str::lower($user->email);

        $donation = DB::transaction(function () use ($campaign, $validated, $user, $donorEmail): Donation {
            $donor = Donor::query()->firstOrCreate(
                ['email' => $donorEmail],
                [
                    'name' => $user->name,
                    'phone' => $validated['donor_phone'] ?? null,
                ],
            );

            return $campaign->donations()->create([
                'donor_id' => $donor->id,
                'amount' => $validated['amount'],
                'payment_method' => 'Card',
                'status' => 'pending',
                'donated_at' => now(),
            ]);
        });

        try {
            $intent = $stripe->createPaymentIntent(
                $donation,
                $campaign,
                $stripe->amountInCents((string) $donation->amount),
                $donorEmail,
            );
        } catch (ApiErrorException $exception) {
            report($exception);
            $stripe->markDonationFailed($donation);

            return response()->json([
                'message' => 'We could not start your payment. Please try again.',
            ], 502);
        }

        if (! is_string($intent->client_secret) || $intent->client_secret === '') {
            Log::error('Stripe created a PaymentIntent without a client secret.', [
                'donation_id' => $donation->id,
                'payment_intent_id' => $intent->id,
            ]);
            $stripe->markDonationFailed($donation);

            return response()->json([
                'message' => 'We could not start your payment. Please try again.',
            ], 502);
        }

        DB::transaction(function () use ($donation, $intent): void {
            $donation->update(['payment_reference' => $intent->id]);
        });

        $request->session()->put("pending_donations.{$donation->id}", true);

        return response()->json([
            'donation_id' => $donation->id,
            'client_secret' => $intent->client_secret,
            'return_url' => route('donations.payment.return', $donation),
        ]);
    }

    public function confirm(
        Request $request,
        Donation $donation,
        StripePaymentService $stripe,
    ): JsonResponse {
        abort_unless(
            $request->session()->has("pending_donations.{$donation->id}")
                && $donation->payment_method === 'Card'
                && is_string($donation->payment_reference)
                && $this->userOwnsDonation($request, $donation),
            404,
        );

        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        if (! hash_equals($donation->payment_reference, $validated['payment_intent_id'])) {
            return response()->json(['message' => 'This payment could not be verified.'], 422);
        }

        try {
            $intent = $stripe->retrievePaymentIntent($donation->payment_reference);
        } catch (ApiErrorException $exception) {
            report($exception);

            return response()->json([
                'message' => 'We could not verify your payment. Please try again.',
            ], 502);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Card payment verification is unavailable. Please contact support.',
            ], 503);
        }

        if (! $stripe->paymentIntentMatchesDonation($intent, $donation)) {
            Log::warning('A Stripe PaymentIntent did not match its donation record.', [
                'donation_id' => $donation->id,
                'payment_intent_id' => $intent->id,
            ]);

            return response()->json(['message' => 'This payment could not be verified.'], 422);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json([
                'message' => 'Your payment has not completed yet. Please try again.',
            ], 409);
        }

        if (! $stripe->markDonationCompleted($donation, $intent)) {
            return response()->json(['message' => 'This payment could not be verified.'], 422);
        }

        $request->session()->put('successful_donation_id', $donation->id);

        return response()->json([
            'redirect_url' => route('donations.thank-you', $donation),
        ]);
    }

    public function paymentReturn(
        Request $request,
        Donation $donation,
        StripePaymentService $stripe,
    ): RedirectResponse {
        abort_unless(
            $request->session()->has("pending_donations.{$donation->id}")
                && $donation->payment_method === 'Card'
                && is_string($donation->payment_reference)
                && $this->userOwnsDonation($request, $donation),
            404,
        );

        $paymentIntentId = $request->query('payment_intent');

        if (! is_string($paymentIntentId) || ! hash_equals($donation->payment_reference, $paymentIntentId)) {
            return redirect()->route('campaigns')
                ->withErrors(['payment' => 'We could not verify your payment. Please contact support if you were charged.']);
        }

        try {
            $intent = $stripe->retrievePaymentIntent($paymentIntentId);
        } catch (ApiErrorException $exception) {
            report($exception);

            return redirect()->route('campaigns')
                ->withErrors(['payment' => 'We could not verify your payment. Please try again.']);
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()->route('campaigns')
                ->withErrors(['payment' => 'Card payment verification is unavailable. Please contact support.']);
        }

        if (! $stripe->paymentIntentMatchesDonation($intent, $donation) || $intent->status !== 'succeeded') {
            return redirect()->route('campaigns')
                ->withErrors(['payment' => 'Your payment has not been confirmed. Please try again.']);
        }

        if (! $stripe->markDonationCompleted($donation, $intent)) {
            return redirect()->route('campaigns')
                ->withErrors(['payment' => 'We could not verify your payment. Please contact support if you were charged.']);
        }

        $request->session()->put('successful_donation_id', $donation->id);

        return redirect()->route('donations.thank-you', $donation);
    }

    public function thankYou(Request $request, Donation $donation): View
    {
        abort_unless(
            (string) $request->session()->get('successful_donation_id') === (string) $donation->id
                && $this->userOwnsDonation($request, $donation)
                && $donation->status === 'completed',
            404,
        );

        $donation->load('campaign');

        return view('donations.thank-you', ['donation' => $donation]);
    }

    public function webhook(Request $request, StripePaymentService $stripe): JsonResponse
    {
        try {
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->json(['message' => 'Invalid Stripe webhook signature or payload.'], 400);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Stripe webhooks are not configured.'], 503);
        }

        if (! in_array($event->type, [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
        ], true)) {
            return response()->json(['received' => true]);
        }

        $intent = $event->data->object;

        if (! $intent instanceof PaymentIntent) {
            return response()->json(['message' => 'Unexpected Stripe event payload.'], 400);
        }

        if (! is_string($event->id) || $event->id === '') {
            return response()->json(['message' => 'Unexpected Stripe event payload.'], 400);
        }

        if (! $stripe->webhookEventMatchesIntent($event->type, $intent)) {
            Log::warning('Stripe webhook type did not match its PaymentIntent status.', [
                'event_id' => $event->id,
                'payment_intent_id' => $intent->id,
                'event_type' => $event->type,
                'payment_intent_status' => $intent->status,
            ]);

            return response()->json(['received' => true]);
        }

        $donationId = $intent->metadata->donation_id ?? null;
        $donation = is_string($donationId) ? Donation::query()->find($donationId) : null;

        if (! $donation instanceof Donation || ! $stripe->paymentIntentMatchesDonation($intent, $donation)) {
            Log::warning('Stripe webhook did not match a donation record.', [
                'payment_intent_id' => $intent->id,
                'donation_id' => $donationId,
            ]);

            return response()->json(['received' => true]);
        }

        ProcessStripeWebhook::dispatch(
            $event->id,
            $event->type,
            [
                'id' => (string) $intent->id,
                'amount' => (int) $intent->amount,
                'amount_received' => (int) $intent->amount_received,
                'currency' => (string) $intent->currency,
                'status' => (string) $intent->status,
                'metadata' => [
                    'donation_id' => (string) $intent->metadata->donation_id,
                    'campaign_id' => (string) $intent->metadata->campaign_id,
                ],
            ],
        );

        return response()->json(['received' => true]);
    }

    private function campaignAcceptsDonations(Campaign $campaign): bool
    {
        return $campaign->status === 'active'
            && ! $campaign->start_date->isAfter(today())
            && ! $campaign->end_date->isBefore(today());
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function userOwnsDonation(Request $request, Donation $donation): bool
    {
        $user = $request->user();

        return $user instanceof User
            && $donation->donor()
                ->where('email', Str::lower($user->email))
                ->exists();
    }

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $donations = $this->filteredDonations($filters)->paginate(10)->withQueryString();

        $summary = Donation::query()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COUNT(DISTINCT donor_id) as donor_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_amount")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->firstOrFail();

        return view('donations', [
            'donations' => $donations,
            'campaigns' => Campaign::query()->orderBy('title')->get(['id', 'title']),
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $donations = $this->filteredDonations($this->validatedFilters($request));

        return response()->streamDownload(function () use ($donations): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            try {
                if (fputcsv($stream, ['Donor', 'Email', 'Campaign', 'Amount', 'Date', 'Payment Method', 'Status'], escape: '') === false) {
                    throw new RuntimeException('Unable to write the CSV header.');
                }

                $donations->chunk(500, function (Collection $donations) use ($stream): void {
                    foreach ($donations as $donation) {
                        $row = [
                            $donation->donor->name,
                            $donation->donor->email,
                            $donation->campaign->title,
                            number_format((float) $donation->amount, 2, '.', ''),
                            $donation->donated_at->format('Y-m-d H:i:s'),
                            $donation->payment_method,
                            ucfirst($donation->status),
                        ];
                        $row = array_map(
                            static fn (string $value): string => preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1 ? "'".$value : $value,
                            $row,
                        );

                        if (fputcsv($stream, $row, escape: '') === false) {
                            throw new RuntimeException('Unable to write a donation row to the CSV.');
                        }
                    }
                });
            } finally {
                fclose($stream);
            }
        }, 'donations.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{search?: string, campaign_id?: string, status?: string, start_date?: string, end_date?: string}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')],
            'status' => ['nullable', Rule::in(['completed', 'pending', 'failed'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    /**
     * @param  array{search?: string, campaign_id?: string, status?: string, start_date?: string, end_date?: string}  $filters
     * @return Builder<Donation>
     */
    private function filteredDonations(array $filters): Builder
    {
        $donationsQuery = Donation::query()
            ->with([
                'donor:id,name,email',
                'campaign:id,title',
            ])
            ->orderByDesc('donated_at')
            ->orderByDesc('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $donationsQuery->where(function (Builder $query) use ($search): void {
                $query->whereHas('donor', function (Builder $donorQuery) use ($search): void {
                    $donorQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('campaign', function (Builder $campaignQuery) use ($search): void {
                    $campaignQuery->where('title', 'like', "%{$search}%");
                })->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['campaign_id'])) {
            $donationsQuery->where('campaign_id', $filters['campaign_id']);
        }

        if (! empty($filters['status'])) {
            $donationsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $donationsQuery->whereDate('donated_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $donationsQuery->whereDate('donated_at', '<=', $filters['end_date']);
        }

        return $donationsQuery;
    }
}
