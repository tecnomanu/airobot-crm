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
            $table->string('country', 2)->default('AR')->after('auto_process_enabled')
                ->comment('Código ISO2 del país objetivo de la campaña');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('city')
                ->comment('Código ISO2 del país del lead');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
