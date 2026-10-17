<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_and_registration_pages_render(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to your account');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Confirm password')
            ->assertDontSee('verify');
    }

    public function test_registration_creates_and_authenticates_an_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Avery Stone',
            'email' => 'avery@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'avery@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        $this->assertSame(UserRole::Member, $user->roleAssignment->role);
        $this->assertDatabaseHas('user_pivot', [
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_registration_rejects_invalid_email_and_mismatched_password(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Avery Stone',
                'email' => 'not-an-email',
                'password' => 'a-secure-password',
                'password_confirmation' => 'a-different-password',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_rejects_incorrect_credentials(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_authenticates_an_account(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
