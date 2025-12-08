<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead Activities - Unified Timeline (Polymorphic Hub)
     *
     * This table serves as the central timeline for all lead events.
     * Uses polymorphic relations to link to specific event types:
     * - LeadCall: Phone call details
     * - LeadMessage: WhatsApp/SMS messages
     * - (Future) LeadNote, LeadStatusChange, etc.
     */
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');

            // Polymorphic columns for subject (the specific event)
            $table->string('subject_type'); // App\Models\LeadCall, App\Models\LeadMessage, etc.
            $table->uuid('subject_id');

            $table->timestamps();

            // Indexes for efficient timeline queries
            $table->index('lead_id');
            $table->index('client_id');
            $table->index(['lead_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
