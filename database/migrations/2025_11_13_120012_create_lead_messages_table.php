<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead Messages - WhatsApp/SMS communication records
     *
     * Stores all message-specific data with:
     * - Content and direction
     * - Channel (WhatsApp, SMS)
     * - Delivery status tracking
     * - Provider integration data
     */
    public function up(): void
    {
        Schema::create('lead_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->uuid('campaign_id')->nullable();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->string('phone')->index();

            // Message content
            $table->text('content');
            $table->string('direction'); // inbound, outbound
            $table->string('channel'); // whatsapp, sms

            // Delivery status
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed

            // Provider integration
            $table->string('external_provider_id')->nullable()->unique();
            $table->json('metadata')->nullable(); // Provider-specific raw data

            // Media attachments
            $table->json('attachments')->nullable(); // URLs to media files

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for common queries
            $table->index('lead_id');
            $table->index('campaign_id');
            $table->index('direction');
            $table->index('channel');
            $table->index('status');
            $table->index(['lead_id', 'created_at']);
            $table->index(['channel', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_messages');
    }
};

