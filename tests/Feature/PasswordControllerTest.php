<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_change_password_in_user_pivot(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('password.update'), [
                'current_password' => 'password',
                'password' => 'a-new-secure-password',
                'password_confirmation' => 'a-new-secure-password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('password-status', 'Your password was updated successfully.');

        $this->assertTrue(Hash::check('a-new-secure-password', $user->fresh()->roleAssignment->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->roleAssignment->password));

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'a-new-secure-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_change_requires_current_and_new_passwords(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('password.update'), [])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'current_password' => 'The current password field is required.',
                'password' => 'The password field is required.',
            ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->roleAssignment->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create();
        $originalPasswordHash = $user->roleAssignment->password;

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'a-new-secure-password',
                'password_confirmation' => 'a-new-secure-password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['current_password' => 'The password is incorrect.']);

        $this->assertSame($originalPasswordHash, $user->fresh()->roleAssignment->password);
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('password.update'), [
                'current_password' => 'password',
                'password' => 'a-new-secure-password',
                'password_confirmation' => 'a-different-secure-password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->roleAssignment->password));
    }

    public function test_password_change_requires_a_new_password_of_at_least_eight_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('password.update'), [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['password' => 'The password field must be at least 8 characters.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->roleAssignment->password));
    }

    public function test_guest_is_redirected_to_login_when_changing_password(): void
    {
        $this->post(route('password.update'), [
            'current_password' => 'password',
            'password' => 'a-new-secure-password',
            'password_confirmation' => 'a-new-secure-password',
        ])->assertRedirect(route('login'));
    }
}
