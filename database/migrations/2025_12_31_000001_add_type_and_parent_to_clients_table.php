<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Client type: internal (AirRobot HQ), direct (current clients), reseller, franchise
            $table->string('type')->default('direct')->after('status');

            // Parent client for reseller/franchise hierarchy (future use)
            $table->uuid('parent_client_id')->nullable()->after('type');

            // Self-referencing FK for parent hierarchy
            $table->foreign('parent_client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();

            $table->index('type');
            $table->index('parent_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['parent_client_id']);
            $table->dropColumn(['type', 'parent_client_id']);
        });
    }
};
