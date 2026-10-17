<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_user_can_save_donor_profile_and_see_it_in_the_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('donors.store'), [
            'name' => 'Jordan Taylor',
            'email' => 'jordan@example.com',
            'phone' => '+1 555 123 4567',
            'country' => 'United States',
            'city' => 'Austin',
            'address' => '123 Main Street',
            'notes' => 'Prefers email contact.',
        ]);

        $response->assertRedirect(route('donors'))
            ->assertSessionHas('status', 'Donor saved successfully.');

        $this->assertDatabaseHas('donors', [
            'name' => 'Jordan Taylor',
            'email' => 'jordan@example.com',
            'phone' => '+1 555 123 4567',
            'country' => 'United States',
            'city' => 'Austin',
            'address' => '123 Main Street',
            'notes' => 'Prefers email contact.',
        ]);

        $this->get(route('donors'))
            ->assertOk()
            ->assertSee('Donor saved successfully.')
            ->assertSee('Jordan Taylor')
            ->assertSee('jordan@example.com')
            ->assertSee('United States');
    }

    public function test_donor_creation_rejects_invalid_and_duplicate_email_without_saving(): void
    {
        $user = User::factory()->create();
        Donor::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($user)
            ->from(route('donors.create'))
            ->post(route('donors.store'), [
                'name' => 'Duplicate Donor',
                'email' => 'existing@example.com',
            ]);

        $response->assertRedirect(route('donors.create'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('donors', 1);
    }

    public function test_donor_creation_requires_a_name_and_valid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('donors.create'))
            ->post(route('donors.store'), [
                'name' => '',
                'email' => 'not-an-email',
            ]);

        $response->assertRedirect(route('donors.create'))
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseCount('donors', 0);
    }

    public function test_guests_cannot_save_donors(): void
    {
        $response = $this->post(route('donors.store'), [
            'name' => 'Unauthenticated Donor',
            'email' => 'guest@example.com',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('donors', 0);
    }
}
