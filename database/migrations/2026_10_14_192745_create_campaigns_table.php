<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('short_description');
            $table->longText('description')->nullable();
            $table->decimal('goal_amount', 12, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('category', 64);
            $table->string('status', 24)->default('active');
            $table->string('image_path')->nullable();
            $table->index(['status', 'start_date']);
            $table->index('category');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
