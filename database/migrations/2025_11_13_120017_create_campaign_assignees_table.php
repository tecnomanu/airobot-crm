<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campaign sellers assignment (round-robin pool)
        Schema::create('campaign_assignees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Each user can only be assigned once per campaign
            $table->unique(['campaign_id', 'user_id']);
        });

        // Round-robin cursor per campaign
        Schema::create('campaign_assignment_cursors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('current_index')->default(0);
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_assignment_cursors');
        Schema::dropIfExists('campaign_assignees');
    }
};

