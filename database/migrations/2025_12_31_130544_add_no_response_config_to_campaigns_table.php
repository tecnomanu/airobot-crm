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
        Schema::table('campaigns', function (Blueprint $table) {
            // No response configuration
            $table->boolean('no_response_action_enabled')
                ->default(false)
                ->after('use_client_whatsapp_defaults')
                ->comment('Enable auto-close on no response');

            $table->unsignedTinyInteger('no_response_max_attempts')
                ->default(3)
                ->after('no_response_action_enabled')
                ->comment('Max attempts before marking as no response');

            $table->unsignedSmallInteger('no_response_timeout_hours')
                ->default(48)
                ->after('no_response_max_attempts')
                ->comment('Hours to wait before auto-closing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'no_response_action_enabled',
                'no_response_max_attempts',
                'no_response_timeout_hours',
            ]);
        });
    }
};
