<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_pivot', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('role');
        });

        DB::table('users')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    $roleAssignmentExists = DB::table('user_pivot')
                        ->where('user_id', $user->id)
                        ->exists();

                    if ($roleAssignmentExists) {
                        DB::table('user_pivot')
                            ->where('user_id', $user->id)
                            ->update(['password' => $user->password]);

                        continue;
                    }

                    DB::table('user_pivot')->insert([
                        'user_id' => $user->id,
                        'role' => 'member',
                        'password' => $user->password,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('user_pivot', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['email_verified_at', 'password', 'created_at', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('user_pivot')
            ->select(['user_id', 'password'])
            ->orderBy('user_id')
            ->chunkById(500, function ($assignments): void {
                foreach ($assignments as $assignment) {
                    DB::table('users')
                        ->where('id', $assignment->user_id)
                        ->update([
                            'password' => $assignment->password,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }, 'user_id');

        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });

        Schema::table('user_pivot', function (Blueprint $table): void {
            $table->dropColumn('password');
        });
    }
};
