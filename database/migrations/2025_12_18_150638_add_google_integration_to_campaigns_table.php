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
            // Se usa string para el ID porque en el futuro podría ser algo distinto a un UUID de integration
            // O si prefieres FK directa:
            $table->foreignUuid('google_integration_id')->nullable()->constrained('google_integrations')->nullOnDelete();
            $table->string('google_spreadsheet_id')->nullable();
            $table->string('google_sheet_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['google_integration_id']);
            $table->dropColumn(['google_integration_id', 'google_spreadsheet_id', 'google_sheet_name']);
        });
    }
};
