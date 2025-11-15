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
        // Verificar y eliminar match_pattern si existe
        if (Schema::hasColumn('campaigns', 'match_pattern')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('match_pattern');
            });
        }
        
        // Asegurar que slug tenga unique constraint
        if (Schema::hasColumn('campaigns', 'slug')) {
            try {
                Schema::table('campaigns', function (Blueprint $table) {
                    $table->unique('slug');
                });
            } catch (\Exception $e) {
                // El constraint ya existe, continuar
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar match_pattern
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('match_pattern')->nullable()->after('status')->unique();
        });
    }
};
