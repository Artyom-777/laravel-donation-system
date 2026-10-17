<?php

namespace App\Jobs;

use App\Services\StripePaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 120];

    /**
     * @param  array{id: string, amount: int, amount_received: int, currency: string, status: string, metadata: array{donation_id: string, campaign_id: string}}  $paymentIntentData
     */
    public function __construct(
        public string $eventId,
        public string $eventType,
        public array $paymentIntentData,
    ) {}

    public function handle(StripePaymentService $stripe): void
    {
        $stripe->processWebhookEvent($this->eventId, $this->eventType, $this->paymentIntentData);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Stripe webhook processing exhausted its retries.', [
            'event_id' => $this->eventId,
            'payment_intent_id' => $this->paymentIntentData['id'] ?? null,
            'exception_class' => $exception === null ? null : $exception::class,
        ]);
    }
}
