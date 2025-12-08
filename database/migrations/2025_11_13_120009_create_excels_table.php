<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->string('name')->default('Hoja sin título');
            $table->json('data')->nullable()->comment('Datos de las celdas: {cellId: {value, formula, style}}');
            $table->json('last_cursor_position')->nullable()->comment('{row, col}');
            $table->json('column_widths')->nullable()->comment('{A: 120, B: 200, ...}');
            $table->json('row_heights')->nullable()->comment('{1: 25, 2: 50, ...}');
            $table->integer('frozen_rows')->default(0);
            $table->integer('frozen_columns')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excels');
    }
};
