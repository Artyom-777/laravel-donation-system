<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_pivot', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['admin', 'guest', 'member'])->default('member');
            $table->timestamps();
        });

        DB::table('user_pivot')->insertUsing(
            ['user_id', 'role'],
            DB::table('users')->select('id')->selectRaw("'member'"),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pivot');
    }
};
