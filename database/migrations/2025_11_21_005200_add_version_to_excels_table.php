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
        Schema::table('excels', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(0)->after('frozen_columns');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('excels', function (Blueprint $table) {
            $table->dropIndex(['version']);
            $table->dropColumn('version');
        });
    }
};
