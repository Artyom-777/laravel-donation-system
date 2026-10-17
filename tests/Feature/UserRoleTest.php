<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserRole}>
     */
    public static function supportedRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'guest' => [UserRole::Guest],
            'member' => [UserRole::Member],
        ];
    }

    public function test_new_users_receive_the_member_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Member, $user->roleAssignment->role);
        $this->assertDatabaseHas('user_pivot', [
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_password_is_stored_only_in_user_pivot_and_user_metadata_columns_are_removed(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Hash::check('password', $user->roleAssignment->password));
        $this->assertFalse(Schema::hasColumn('users', 'password'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
        $this->assertFalse(Schema::hasColumn('users', 'created_at'));
        $this->assertFalse(Schema::hasColumn('users', 'updated_at'));
    }

    #[DataProvider('supportedRoles')]
    public function test_users_can_store_each_supported_role(UserRole $role): void
    {
        $user = User::factory()->create();
        $assignment = $user->roleAssignment;
        $assignment->role = $role;
        $assignment->save();

        $this->assertSame($role, $user->fresh()->roleAssignment->role);
        $this->assertDatabaseCount('user_pivot', 1);
    }

    public function test_user_role_assignment_rejects_unsupported_roles(): void
    {
        $user = User::factory()->create();
        $assignment = $user->roleAssignment;

        $this->expectException(\ValueError::class);

        $assignment->role = 'superadmin';
    }
}
