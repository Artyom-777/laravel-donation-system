<?php

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhook;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

class DonationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_create_a_stripe_payment_intent_for_a_campaign_donation(): void
    {
        $user = User::factory()->create([
            'name' => 'Taylor Reed',
            'email' => 'taylor@example.com',
        ]);
        $campaign = $this->activeCampaign($user);
        config(['services.stripe.key' => 'pk_test']);

        $this->mock(StripePaymentService::class, function (MockInterface $mock) use ($campaign): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('amountInCents')->once()->with('25.50')->andReturn(2550);
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->withArgs(fn (Donation $donation, Campaign $receivedCampaign, int $amount, string $email): bool => $receivedCampaign->is($campaign)
                    && $donation->amount === '25.50'
                    && $amount === 2550
                    && $email === 'taylor@example.com')
                ->andReturnUsing(fn (Donation $donation): PaymentIntent => $this->paymentIntent($donation, 'requires_payment_method', 0));
        });

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Untrusted Submitted Name',
            'donor_email' => 'attacker@example.com',
            'donor_phone' => '555-0112',
            'amount' => '25.50',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['donation_id', 'client_secret', 'return_url']);

        $donor = Donor::query()->where('email', $user->email)->firstOrFail();
        $this->assertDatabaseCount('donors', 1);
        $this->assertSame('Taylor Reed', $donor->name);
        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'donor_id' => $donor->id,
            'amount' => '25.50',
            'payment_method' => 'Card',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('donations', ['payment_reference' => 'pi_123']);
    }

    public function test_campaign_donation_reuses_existing_donor_without_overwriting_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Account Holder',
            'email' => 'existing@example.com',
        ]);
        $campaign = $this->activeCampaign($user);
        config(['services.stripe.key' => 'pk_test']);
        $donor = Donor::factory()->create([
            'name' => 'Original Donor Name',
            'email' => 'existing@example.com',
        ]);

        $this->mock(StripePaymentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('amountInCents')->once()->with('10.00')->andReturn(1000);
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->andReturnUsing(fn (Donation $donation): PaymentIntent => $this->paymentIntent($donation, 'requires_payment_method', 0));
        });

        $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Different Submitted Name',
            'donor_email' => 'attacker@example.com',
            'amount' => '10.00',
        ])->assertOk();

        $this->assertDatabaseCount('donors', 1);
        $this->assertDatabaseHas('donors', [
            'id' => $donor->id,
            'name' => 'Original Donor Name',
        ]);
        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'donor_id' => $donor->id,
            'status' => 'pending',
            'payment_method' => 'Card',
        ]);
    }

    public function test_campaign_donation_rejects_invalid_amount_without_creating_records(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = $this->activeCampaign($user);

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Taylor Reed',
            'donor_email' => 'taylor@example.com',
            'amount' => '0.25',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'The amount field must be at least 0.50.');

        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_campaign_donation_rejects_amounts_above_the_stripe_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = $this->activeCampaign($user);

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Taylor Reed',
            'donor_email' => 'taylor@example.com',
            'amount' => '1000000.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_campaign_donation_is_rejected_when_campaign_is_not_active(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'status' => 'upcoming',
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Taylor Reed',
            'donor_email' => 'taylor@example.com',
            'amount' => '10.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'This campaign is not currently accepting donations.');

        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_campaign_donation_is_rejected_when_campaign_is_completed(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'amount' => '10.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'This campaign is not currently accepting donations.');
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_donation_attempt_after_campaign_end_date_marks_campaign_completed_and_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'status' => 'active',
            'start_date' => today()->subDays(10)->toDateString(),
            'end_date' => today()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson(route('campaigns.donations.store', $campaign), [
            'amount' => '10.00',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_guests_cannot_create_campaign_donations(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active']);

        $response = $this->postJson(route('campaigns.donations.store', $campaign), [
            'donor_name' => 'Taylor Reed',
            'donor_email' => 'taylor@example.com',
            'amount' => '10.00',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('donations', 0);
    }

    public function test_payment_confirmation_completes_only_a_matching_successful_stripe_payment(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create(['email' => $user->email]);
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '25.50',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock) use ($donation): void {
            $mock->shouldReceive('retrievePaymentIntent')
                ->once()
                ->with('pi_123')
                ->andReturn($this->paymentIntent($donation, 'succeeded', 2550));
        });

        $response = $this->actingAs($user)
            ->withSession(['pending_donations' => [$donation->id => true]])
            ->postJson(route('donations.confirm', $donation), ['payment_intent_id' => 'pi_123']);

        $response->assertOk()
            ->assertJsonPath('redirect_url', route('donations.thank-you', $donation));
        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'completed',
            'payment_reference' => 'pi_123',
        ]);

        $this->get(route('donations.thank-you', $donation))
            ->assertOk()
            ->assertSee('Thank you for making a difference!')
            ->assertSee('$25.50');
    }

    public function test_payment_confirmation_keeps_a_donation_pending_until_stripe_reports_success(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create(['email' => $user->email]);
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '10.00',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock) use ($donation): void {
            $mock->shouldReceive('retrievePaymentIntent')
                ->once()
                ->andReturn($this->paymentIntent($donation, 'requires_payment_method', 0));
        });

        $this->actingAs($user)
            ->withSession(['pending_donations' => [$donation->id => true]])
            ->postJson(route('donations.confirm', $donation), ['payment_intent_id' => 'pi_123'])
            ->assertStatus(409);

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
        ]);
    }

    public function test_payment_confirmation_rejects_a_stripe_payment_with_the_wrong_amount(): void
    {
        $user = User::factory()->create([
            'email' => 'donor@example.com',
        ]);
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create(['email' => $user->email]);
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '10.00',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->partialMock(StripePaymentService::class, function (MockInterface $mock) use ($donation): void {
            $mock->shouldReceive('retrievePaymentIntent')
                ->once()
                ->andReturn($this->paymentIntent($donation, 'succeeded', 1000, 999));
        });

        $this->actingAs($user)
            ->withSession(['pending_donations' => [$donation->id => true]])
            ->postJson(route('donations.confirm', $donation), ['payment_intent_id' => 'pi_123'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
        ]);
    }

    public function test_authenticated_user_cannot_confirm_another_users_donation(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $otherDonor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($otherDonor)->create([
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->actingAs($user)
            ->withSession(['pending_donations' => [$donation->id => true]])
            ->postJson(route('donations.confirm', $donation), ['payment_intent_id' => 'pi_123'])
            ->assertNotFound();

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
        ]);
    }

    public function test_authenticated_user_cannot_open_another_users_thank_you_page(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $otherDonor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($otherDonor)->create([
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->withSession(['successful_donation_id' => $donation->id])
            ->get(route('donations.thank-you', $donation))
            ->assertNotFound();
    }

    public function test_authenticated_user_cannot_return_from_another_users_payment(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $otherDonor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($otherDonor)->create([
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->actingAs($user)
            ->withSession(['pending_donations' => [$donation->id => true]])
            ->get(route('donations.payment.return', [
                'donation' => $donation,
                'payment_intent' => 'pi_123',
            ]))
            ->assertNotFound();
    }

    public function test_stripe_webhook_queues_a_verified_successful_payment(): void
    {
        Queue::fake([ProcessStripeWebhook::class]);
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $campaign = Campaign::factory()->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '10.00',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);
        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'api_version' => '2025-01-27.acacia',
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'pi_123',
                    'object' => 'payment_intent',
                    'amount' => 1000,
                    'amount_received' => 1000,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                    'metadata' => [
                        'donation_id' => (string) $donation->id,
                        'campaign_id' => (string) $campaign->id,
                    ],
                ],
            ],
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => ['id' => null, 'idempotency_key' => null],
            'type' => 'payment_intent.succeeded',
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk()
            ->assertJsonPath('received', true);

        Queue::assertPushed(ProcessStripeWebhook::class, fn (ProcessStripeWebhook $job): bool => $job->eventId === 'evt_test'
            && $job->eventType === 'payment_intent.succeeded'
            && $job->paymentIntentData['id'] === 'pi_123'
            && $job->paymentIntentData['amount'] === 1000
            && $job->paymentIntentData['metadata']['donation_id'] === (string) $donation->id);
        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
        ]);
    }

    public function test_stripe_webhook_rejects_an_invalid_signature_without_updating_donations(): void
    {
        Queue::fake([ProcessStripeWebhook::class]);
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $campaign = Campaign::factory()->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=invalid',
        ], '{"id":"evt_test","object":"event","type":"payment_intent.succeeded"}')
            ->assertBadRequest();

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
        ]);
        Queue::assertNotPushed(ProcessStripeWebhook::class);
    }

    public function test_queued_stripe_webhook_is_idempotent_and_does_not_let_a_late_failure_reverse_success(): void
    {
        $campaign = Campaign::factory()->create();
        $campaign->update(['goal_amount' => '10.00']);
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '10.00',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);
        $paymentIntentData = [
            'id' => 'pi_123',
            'amount' => 1000,
            'amount_received' => 1000,
            'currency' => 'usd',
            'status' => 'succeeded',
            'metadata' => [
                'donation_id' => (string) $donation->id,
                'campaign_id' => (string) $campaign->id,
            ],
        ];
        $successJob = new ProcessStripeWebhook('evt_success', 'payment_intent.succeeded', $paymentIntentData);
        $failureJob = new ProcessStripeWebhook('evt_failure', 'payment_intent.payment_failed', [
            ...$paymentIntentData,
            'amount_received' => 0,
            'status' => 'requires_payment_method',
        ]);

        $successJob->handle(app(StripePaymentService::class));
        $successJob->handle(app(StripePaymentService::class));
        $failureJob->handle(app(StripePaymentService::class));

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'completed',
            'payment_reference' => 'pi_123',
        ]);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
    }

    public function test_queued_success_event_does_not_complete_a_payment_with_a_non_successful_intent_status(): void
    {
        $campaign = Campaign::factory()->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '10.00',
            'payment_method' => 'Card',
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);
        $job = new ProcessStripeWebhook('evt_mismatched', 'payment_intent.succeeded', [
            'id' => 'pi_123',
            'amount' => 1000,
            'amount_received' => 0,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => [
                'donation_id' => (string) $donation->id,
                'campaign_id' => (string) $campaign->id,
            ],
        ]);

        $job->handle(app(StripePaymentService::class));

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'pending',
            'payment_reference' => 'pi_123',
        ]);
    }

    public function test_authenticated_user_sees_database_donations_and_summary(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Community Food Fund',
        ]);
        $donor = Donor::factory()->create([
            'name' => 'Rachel Adams',
            'email' => 'rachel@example.com',
        ]);
        Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => 125.50,
            'payment_method' => 'Credit Card',
            'status' => 'completed',
            'donated_at' => '2025-03-15 12:00:00',
        ]);
        Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => 20,
            'status' => 'pending',
            'donated_at' => '2025-03-14 12:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('donations'))
            ->assertOk()
            ->assertSee('Rachel Adams')
            ->assertSee('Community Food Fund')
            ->assertSee('Credit Card')
            ->assertSee('$125.50')
            ->assertSee('2 donation records')
            ->assertSee('1</strong><small>Unique donors', false)
            ->assertSee('1</strong><small>Successful payments', false)
            ->assertSee('1</strong><small>Awaiting payment', false)
            ->assertDontSee('John Smith');
    }

    public function test_donation_list_can_be_filtered_by_search_campaign_status_and_dates(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Community Food Fund',
        ]);
        $otherCampaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Library Project',
        ]);
        $matchingDonor = Donor::factory()->create([
            'name' => 'Rachel Adams',
            'email' => 'rachel@example.com',
        ]);
        $wrongDonor = Donor::factory()->create([
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.com',
        ]);

        Donation::factory()->for($campaign)->for($matchingDonor)->create([
            'status' => 'completed',
            'donated_at' => '2025-03-15 12:00:00',
        ]);
        Donation::factory()->for($campaign)->for($wrongDonor)->create([
            'status' => 'pending',
            'donated_at' => '2025-03-15 12:00:00',
        ]);
        Donation::factory()->for($otherCampaign)->for($matchingDonor)->create([
            'status' => 'completed',
            'donated_at' => '2025-03-15 12:00:00',
        ]);
        Donation::factory()->for($campaign)->for($matchingDonor)->create([
            'status' => 'completed',
            'donated_at' => '2025-02-15 12:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('donations', [
                'search' => 'Rachel',
                'campaign_id' => $campaign->id,
                'status' => 'completed',
                'start_date' => '2025-03-01',
                'end_date' => '2025-03-31',
            ]))
            ->assertOk()
            ->assertSee('Rachel Adams')
            ->assertSee('Community Food Fund')
            ->assertDontSee('Morgan Lee')
            ->assertSee('Showing 1–1 of 1 donations');
    }

    public function test_donation_pagination_preserves_active_filters(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create();

        Donation::factory()
            ->count(11)
            ->for($campaign)
            ->for($donor)
            ->state(['status' => 'completed'])
            ->create();

        $this->actingAs($user)
            ->get(route('donations', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('Showing 1–10 of 11 donations')
            ->assertSee('status=completed&amp;page=2', false);
    }

    public function test_export_downloads_all_matching_donations_as_safe_csv(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $formulaDonor = Donor::factory()->create([
            'name' => '=HYPERLINK("https://example.com","Open")',
            'email' => '=SUM(1,1)',
        ]);
        $otherDonor = Donor::factory()->create([
            'name' => 'Pending Donor',
            'email' => 'pending@example.com',
        ]);

        Donation::factory()->for($campaign)->for($formulaDonor)->create([
            'status' => 'completed',
            'donated_at' => '2025-03-15 12:00:00',
        ]);
        Donation::factory()->for($campaign)->for($otherDonor)->create([
            'status' => 'pending',
            'donated_at' => '2025-03-14 12:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get(route('donations.export', ['status' => 'completed']));

        $response->assertDownload('donations.csv')
            ->assertStreamed();

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'=SUM(1,1)", $csv);
        $this->assertStringNotContainsString('Pending Donor', $csv);
    }

    public function test_donation_date_range_rejects_an_end_date_before_the_start_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('donations', [
                'start_date' => '2025-03-02',
                'end_date' => '2025-03-01',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_guests_are_redirected_from_the_donations_page_to_login(): void
    {
        $this->get(route('donations'))
            ->assertRedirect(route('login'));
    }

    private function activeCampaign(User $user): Campaign
    {
        return Campaign::factory()->for($user, 'creator')->create([
            'status' => 'active',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ]);
    }

    private function paymentIntent(
        Donation $donation,
        string $status,
        int $amountReceived,
        ?int $amountOverride = null,
    ): PaymentIntent {
        return PaymentIntent::constructFrom([
            'id' => 'pi_123',
            'object' => 'payment_intent',
            'amount' => $amountOverride ?? (int) round((float) $donation->amount * 100),
            'amount_received' => $amountReceived,
            'client_secret' => 'pi_123_secret_test',
            'currency' => 'usd',
            'status' => $status,
            'metadata' => [
                'donation_id' => (string) $donation->id,
                'campaign_id' => (string) $donation->campaign_id,
            ],
        ]);
    }
}
