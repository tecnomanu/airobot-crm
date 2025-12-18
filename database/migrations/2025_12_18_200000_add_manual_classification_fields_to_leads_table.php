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
            $table->string('manual_classification')->nullable()->after('tags');
            $table->text('decision_notes')->nullable()->after('manual_classification');
            $table->boolean('ai_agent_active')->default(false)->after('decision_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['manual_classification', 'decision_notes', 'ai_agent_active']);
        });
    }
};
