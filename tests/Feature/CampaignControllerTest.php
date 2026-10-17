<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_create_campaign_with_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $image = UploadedFile::fake()->createWithContent(
            'campaign.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );

        $response = $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Build a Community School',
            'short_description' => 'Give children a safe place to learn.',
            'description' => 'Every donation helps build classrooms.',
            'goal_amount' => '12500.50',
            'start_date' => '2026-10-15',
            'end_date' => '2026-12-31',
            'category' => 'education',
            'status' => 'active',
            'created_by' => $otherUser->id,
            'image' => $image,
        ]);

        $response->assertRedirect(route('campaigns'))
            ->assertSessionHas('status', 'Campaign created successfully.');

        $campaign = Campaign::query()->firstOrFail();

        $this->assertModelExists($campaign);
        $this->assertSame($user->id, $campaign->created_by);
        $this->assertSame('Build a Community School', $campaign->title);
        $this->assertSame('12500.50', $campaign->goal_amount);
        $this->assertSame('Every donation helps build classrooms.', $campaign->description);
        $this->assertNotNull($campaign->image_path);
        Storage::disk('public')->assertExists($campaign->image_path);
    }

    public function test_campaign_creation_rejects_an_end_date_before_its_start_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('campaigns.create'))->post(route('campaigns.store'), [
            'title' => 'Build a Community School',
            'short_description' => 'Give children a safe place to learn.',
            'goal_amount' => '1000.00',
            'start_date' => '2026-12-31',
            'end_date' => '2026-10-15',
            'category' => 'education',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('campaigns.create'))
            ->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_transaction_rolls_back_record_and_uploaded_image_when_creation_fails(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $image = UploadedFile::fake()->createWithContent(
            'campaign.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );

        Campaign::created(static function (): never {
            throw new RuntimeException('Campaign creation failed.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('campaigns.store'), [
                'title' => 'Build a Community School',
                'short_description' => 'Give children a safe place to learn.',
                'goal_amount' => '1000.00',
                'start_date' => '2026-10-15',
                'end_date' => '2026-12-31',
                'category' => 'education',
                'status' => 'active',
                'image' => $image,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Campaign creation failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('campaigns', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_guests_cannot_create_campaigns(): void
    {
        $response = $this->post(route('campaigns.store'), []);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_creator_can_delete_a_campaign_without_donations(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'image_path' => 'campaigns/community.jpg',
        ]);
        Storage::disk('public')->put($campaign->image_path, 'campaign image');

        $response = $this->actingAs($user)
            ->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns'))
            ->assertSessionHas('status', 'Campaign deleted successfully.');
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        Storage::disk('public')->assertMissing('campaigns/community.jpg');
    }

    public function test_campaign_with_donation_records_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create();
        Donation::factory()->for($campaign)->for($donor)->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->from(route('campaigns'))
            ->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns'))
            ->assertSessionHasErrors([
                'campaign' => 'This campaign cannot be deleted because it has donation records.',
            ]);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_only_the_campaign_creator_can_delete_a_campaign(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->for($creator, 'creator')->create();

        $this->actingAs($otherUser)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_guests_are_redirected_when_trying_to_delete_a_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $this->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_listing_displays_database_campaign_and_completed_donation_totals(): void
    {
        config([
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
        ]);
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Community Learning Center',
            'short_description' => 'A learning space for neighborhood children.',
            'goal_amount' => '1000.00',
            'status' => 'active',
            'image_path' => null,
        ]);
        $donor = Donor::factory()->create();

        Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '100.00',
            'status' => 'completed',
        ]);
        Donation::factory()->for($campaign)->for($donor)->create([
            'amount' => '50.00',
            'status' => 'completed',
        ]);
        Donation::factory()->for($campaign)->create([
            'amount' => '900.00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('campaigns'));

        $response->assertOk()
            ->assertSee('Community Learning Center')
            ->assertSee('Signed-in account')
            ->assertSee('Receipt email')
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('A learning space for neighborhood children.')
            ->assertSee('$150.00')
            ->assertSee('$1,000.00')
            ->assertSee('1 donor')
            ->assertSee('aria-valuenow="15"', false)
            ->assertSee('data-campaign-react-button', false)
            ->assertSee('Donate Now')
            ->assertSee('Your card is processed securely by Stripe.')
            ->assertSee('Card payments are not configured yet.')
            ->assertSee(route('campaigns.donations.store', $campaign), false)
            ->assertSee(route('campaigns.destroy', $campaign), false)
            ->assertSee('Delete')
            ->assertDontSee('Build a School');
    }

    public function test_campaign_listing_completes_expired_campaign_and_disables_donations(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Expired Community Fund',
            'status' => 'active',
            'start_date' => today()->subDays(10)->toDateString(),
            'end_date' => today()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('campaigns'));

        $response->assertOk()
            ->assertSee('Expired Community Fund')
            ->assertSee('campaign-completed-button', false)
            ->assertSee('data-campaign-status="completed"', false)
            ->assertDontSee('Donate Now');
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
    }

    public function test_campaign_listing_renders_stripe_card_element_when_keys_are_configured(): void
    {
        config([
            'services.stripe.key' => 'pk_test',
            'services.stripe.secret' => 'sk_test',
        ]);
        $user = User::factory()->create();
        Campaign::factory()->for($user, 'creator')->create([
            'status' => 'active',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('campaigns'))
            ->assertOk()
            ->assertSee('data-stripe-donation-form', false)
            ->assertSee('data-stripe-card', false)
            ->assertSee('Donate securely with card')
            ->assertDontSee('Card payments are not configured yet.');
    }

    public function test_campaign_listing_applies_database_search_and_status_filters(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Clean River Project',
            'short_description' => 'Restore clean water locally.',
            'status' => 'active',
        ]);
        Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Community Food Drive',
            'short_description' => 'Support local families.',
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($user)->get(route('campaigns', [
            'search' => 'Clean',
            'status' => 'active',
            'sort' => 'goal-high',
        ]));

        $response->assertOk()
            ->assertSee('Clean River Project')
            ->assertDontSee('Community Food Drive')
            ->assertSee('data-campaign-status-filters', false)
            ->assertSee('data-active-status="active"', false)
            ->assertSee('data-count-all="2"', false)
            ->assertSee('data-count-active="1"', false)
            ->assertSee('data-count-completed="0"', false)
            ->assertSee('data-count-upcoming="1"', false)
            ->assertSee('data-search="Clean"', false)
            ->assertSee('data-sort="goal-high"', false);
    }

    public function test_campaign_listing_paginates_database_results(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->count(10)->for($user, 'creator')->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('campaigns', ['page' => 2]));

        $response->assertOk()
            ->assertSee('campaigns?page=1', false)
            ->assertSee('campaigns?page=2', false);
    }
}
