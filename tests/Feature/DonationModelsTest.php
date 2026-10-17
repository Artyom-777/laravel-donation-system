<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaigns_and_donations_are_related_to_their_owners(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->for($user, 'creator')->create();
        $donor = Donor::factory()->create();
        $donation = Donation::factory()
            ->for($campaign)
            ->for($donor)
            ->create();

        $this->assertModelExists($campaign);
        $this->assertModelExists($donor);
        $this->assertModelExists($donation);

        $this->assertSame($user->id, $campaign->creator->id);
        $this->assertSame($campaign->id, $donation->campaign->id);
        $this->assertSame($donor->id, $donation->donor->id);
        $this->assertSame($donation->id, $donor->donations()->firstOrFail()->id);
    }
}
