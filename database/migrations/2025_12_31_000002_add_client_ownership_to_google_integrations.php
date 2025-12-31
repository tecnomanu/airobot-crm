<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_integrations', function (Blueprint $table) {
            // Client that owns this integration (tenant)
            $table->uuid('client_id')->nullable()->after('id');

            // Rename user_id to created_by_user_id for clarity (audit trail)
            $table->renameColumn('user_id', 'created_by_user_id');
        });

        // Add FK after rename (SQLite requires separate statement)
        Schema::table('google_integrations', function (Blueprint $table) {
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->index('client_id');

            // Remove unique constraint on google_id (same google account can be used by different clients)
            $table->dropUnique(['google_id']);

            // Add composite unique: one google account per client
            $table->unique(['client_id', 'google_id']);
        });
    }

    public function down(): void
    {
        Schema::table('google_integrations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropUnique(['client_id', 'google_id']);
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');

            // Restore original unique
            $table->unique('google_id');

            $table->renameColumn('created_by_user_id', 'user_id');
        });
    }
};

