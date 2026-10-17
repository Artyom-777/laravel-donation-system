<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(array_map(
                static fn (UserRole $role): string => $role->value,
                UserRole::cases(),
            ))],
        ]);

        $userQuery = User::query()
            ->with('roleAssignment')
            ->withCount('campaigns');

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $userQuery->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roleAssignment', fn (Builder $roleQuery) => $roleQuery->where('role', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['role'])) {
            $userQuery->whereHas(
                'roleAssignment',
                fn (Builder $query) => $query->where('role', $filters['role']),
            );
        }

        $users = $userQuery
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(5)
            ->withQueryString();

        $roleCounts = UserRoleAssignment::query()
            ->select('role', DB::raw('COUNT(*) as role_count'))
            ->groupBy('role')
            ->pluck('role_count', 'role');

        return view('users', [
            'users' => $users,
            'userCount' => User::query()->count(),
            'roleCounts' => $roleCounts,
            'roles' => UserRole::cases(),
            'filters' => $filters,
        ]);
    }
}
