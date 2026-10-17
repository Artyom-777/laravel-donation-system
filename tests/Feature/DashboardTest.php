<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_campaign_listing(): void
    {
        $user = User::factory()->create([
            'name' => 'Avery Stone',
            'email' => 'avery@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Avery Stone')
            ->assertSee('Donation Statistics')
            ->assertSee('Recent Donations')
            ->assertSee('Total Donations')
            ->assertSee(route('donations'), false)
            ->assertSee('data-account-menu-root', false)
            ->assertSee(route('password.update'), false)
            ->assertDontSee('notification-link')
            ->assertSee('No donation records yet.')
            ->assertDontSee('Browse and support our active donation campaigns');
    }

    public function test_account_menu_and_sidebar_controls_are_present_on_every_authenticated_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            'dashboard',
            'campaigns',
            'campaigns.create',
            'donations',
            'donors',
            'donors.create',
            'users',
        ] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('data-account-menu-root', false)
                ->assertSee('data-sidebar-toggle', false)
                ->assertDontSee('notification-link');
        }
    }

    public function test_dashboard_displays_live_donation_and_campaign_statistics(): void
    {
        $this->travelTo('2026-10-20 12:00:00');

        $user = User::factory()->create();
        User::factory()->create();

        $activeCampaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Active Community Campaign',
            'status' => 'active',
            'goal_amount' => 1000,
            'start_date' => '2026-10-01',
            'end_date' => '2026-11-30',
        ]);
        $completedCampaign = Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Completed Library Campaign',
            'status' => 'completed',
            'goal_amount' => 500,
        ]);

        $firstDonor = Donor::factory()->create(['name' => 'Alex Rivera']);
        $secondDonor = Donor::factory()->create(['name' => 'Jamie Chen']);
        $pendingDonor = Donor::factory()->create(['name' => 'Taylor Blake']);

        Donation::factory()->for($activeCampaign)->for($firstDonor)->create([
            'amount' => 120,
            'status' => 'completed',
            'donated_at' => '2026-10-05 10:00:00',
        ]);
        Donation::factory()->for($completedCampaign)->for($secondDonor)->create([
            'amount' => 30,
            'status' => 'completed',
            'donated_at' => '2026-09-10 10:00:00',
        ]);
        Donation::factory()->for($activeCampaign)->for($pendingDonor)->create([
            'amount' => 500,
            'status' => 'pending',
            'donated_at' => '2026-10-19 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<span class="stat-label">Total Donations</span><strong class="stat-value">$150.00</strong>', false)
            ->assertSee('<span class="stat-label">Total Donors</span><strong class="stat-value">2</strong>', false)
            ->assertSee('<span class="stat-label">Active Campaigns</span><strong class="stat-value">1</strong>', false)
            ->assertSee('<span class="stat-label">Total Users</span><strong class="stat-value">2</strong>', false)
            ->assertSee('Active Community Campaign')
            ->assertSee('Completed Library Campaign')
            ->assertSee('Alex Rivera')
            ->assertSee('Jamie Chen')
            ->assertSee('Taylor Blake')
            ->assertSee('Pending')
            ->assertSee('Sep: $30.00')
            ->assertSee('Oct: $120.00')
            ->assertDontSee('John Smith');
    }

    public function test_authenticated_user_sees_database_campaigns_on_their_own_page(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Community Learning Center',
            'short_description' => 'A learning space for neighborhood children.',
            'status' => 'active',
        ]);
        Campaign::factory()->for($user, 'creator')->create([
            'title' => 'Clean River Project',
            'short_description' => 'Restore clean water to local communities.',
            'status' => 'upcoming',
        ]);

        $this->actingAs($user)
            ->get(route('campaigns'))
            ->assertOk()
            ->assertSee('Browse and support our active donation campaigns')
            ->assertSee('All Campaigns')
            ->assertSee('Community Learning Center')
            ->assertSee('Clean River Project')
            ->assertSee(route('campaigns.create'), false)
            ->assertSee('Active')
            ->assertSee('Upcoming')
            ->assertDontSee('Donation Statistics');
    }

    public function test_authenticated_user_can_open_campaign_creation_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('campaigns.create'))
            ->assertOk()
            ->assertSee('Add New Campaign')
            ->assertSee('Campaign Information')
            ->assertSee('Goal Amount')
            ->assertSee('Campaign Image')
            ->assertSee('Save Campaign')
            ->assertSee(route('campaigns.store'), false)
            ->assertSee('name="goal_amount"', false);
    }

    public function test_campaigns_redirects_guests_to_login(): void
    {
        $this->get(route('campaigns'))
            ->assertRedirect(route('login'));
    }

    public function test_campaign_creation_page_redirects_guests_to_login(): void
    {
        $this->get(route('campaigns.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_donations_page(): void
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
            'status' => 'pending',
            'payment_method' => 'Credit Card',
        ]);

        $this->actingAs($user)
            ->get(route('donations'))
            ->assertOk()
            ->assertSee('Manage and view all donations received through your platform.')
            ->assertSee('Total Donations')
            ->assertSee('Total Donors')
            ->assertSee('Recent Donations')
            ->assertSee('Rachel Adams')
            ->assertSee('Community Food Fund')
            ->assertSee('Credit Card')
            ->assertSee('Pending')
            ->assertDontSee('John Smith')
            ->assertDontSee('Browse and support our active donation campaigns');
    }

    public function test_donations_page_redirects_guests_to_login(): void
    {
        $this->get(route('donations'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_donors_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('donors'))
            ->assertOk()
            ->assertSee('View and manage all donors who have supported your campaigns.')
            ->assertSee('Total Donors')
            ->assertSee('Repeat Donors')
            ->assertSee('Donors List')
            ->assertSee('No donors have been added yet.')
            ->assertSee('Filter by country')
            ->assertSee('Add New Donor')
            ->assertSee(route('donors.create'), false);
    }

    public function test_donors_page_redirects_guests_to_login(): void
    {
        $this->get(route('donors'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_donor_creation_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('donors.create'))
            ->assertOk()
            ->assertSee('Add New Donor')
            ->assertSee('Donor Information')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Phone Number')
            ->assertSee('Country')
            ->assertSee('City')
            ->assertSee('Address')
            ->assertSee('Additional Information')
            ->assertSee('Notes')
            ->assertSee('Save Donor')
            ->assertSee('method="POST"', false)
            ->assertSee(route('donors.store'), false)
            ->assertSee(route('donors'), false);
    }

    public function test_donor_creation_page_redirects_guests_to_login(): void
    {
        $this->get(route('donors.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_users_page(): void
    {
        $user = User::factory()->create();
        $listedUser = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);
        $listedUser->roleAssignment()->update(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('users'))
            ->assertOk()
            ->assertSee('Manage all users who have access to the donation system.')
            ->assertSee('Total Users')
            ->assertSee('Members')
            ->assertSee('Admins')
            ->assertSee('Guests')
            ->assertSee('Users List')
            ->assertSee('John Smith')
            ->assertSee('Filter by role')
            ->assertSee('Account ID')
            ->assertSee('Campaigns')
            ->assertDontSee('Anna Brown')
            ->assertDontSee('Last Login');
    }

    public function test_users_page_filters_real_users_by_search_and_role(): void
    {
        $admin = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);
        $admin->roleAssignment()->update(['role' => 'admin']);

        User::factory()->create([
            'name' => 'Jane Member',
            'email' => 'jane@example.com',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('users', ['search' => 'John', 'role' => 'admin']))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('john@example.com')
            ->assertDontSee('Jane Member');
    }

    public function test_users_page_redirects_guests_to_login(): void
    {
        $this->get(route('users'))
            ->assertRedirect(route('login'));
    }
}
