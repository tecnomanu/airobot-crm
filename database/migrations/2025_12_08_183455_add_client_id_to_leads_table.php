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
        Schema::table('leads', function (Blueprint $table) {
            // Add direct client relationship (decoupled from campaign)
            $table->uuid('client_id')->nullable()->after('campaign_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');

            // Make campaign_id nullable since leads can exist without campaigns
            $table->uuid('campaign_id')->nullable()->change();

            // Add country field
            $table->string('country')->nullable()->after('city');

            // Add intention tracking fields (migrated from separate tracking)
            $table->string('intention_origin')->nullable()->after('intention')
                ->comment('ivr, whatsapp, manual, api');
            $table->string('intention_status')->nullable()->after('intention_origin')
                ->comment('pending, qualified, disqualified, contacted');
            $table->timestamp('intention_decided_at')->nullable()->after('intention_status');

            // Webhook tracking for intention
            $table->boolean('intention_webhook_sent')->default(false)->after('webhook_result');
            $table->timestamp('intention_webhook_sent_at')->nullable();
            $table->text('intention_webhook_response')->nullable();
            $table->integer('intention_webhook_status')->nullable();

            // Add indexes for new filtering capabilities
            $table->index('client_id');
            $table->index(['client_id', 'status']);
            $table->index('intention_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['client_id']);

            // Drop indexes
            $table->dropIndex(['client_id']);
            $table->dropIndex(['client_id', 'status']);
            $table->dropIndex(['intention_status']);

            // Drop columns
            $table->dropColumn([
                'client_id',
                'country',
                'intention_origin',
                'intention_status',
                'intention_decided_at',
                'intention_webhook_sent',
                'intention_webhook_sent_at',
                'intention_webhook_response',
                'intention_webhook_status',
            ]);
        });
    }
};
