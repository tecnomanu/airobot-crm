<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guardar datos existentes si los hay
        $existingData = DB::table('excels')->get();

        // Dropear y recrear la tabla con UUID
        Schema::dropIfExists('excels');
        
        Schema::create('excels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->default('Hoja sin título');
            $table->json('data')->nullable()->comment('Datos de las celdas: {cellId: {value, formula, style}}');
            $table->json('last_cursor_position')->nullable()->comment('{row, col}');
            $table->json('column_widths')->nullable()->comment('{A: 120, B: 200, ...}');
            $table->json('row_heights')->nullable()->comment('{1: 25, 2: 50, ...}');
            $table->integer('frozen_rows')->default(0);
            $table->integer('frozen_columns')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Restaurar datos con nuevos UUIDs
        foreach ($existingData as $row) {
            DB::table('excels')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $row->user_id,
                'client_id' => $row->client_id,
                'name' => $row->name,
                'data' => $row->data,
                'last_cursor_position' => $row->last_cursor_position,
                'column_widths' => $row->column_widths,
                'row_heights' => $row->row_heights,
                'frozen_rows' => $row->frozen_rows,
                'frozen_columns' => $row->frozen_columns,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Guardar datos
        $existingData = DB::table('excels')->get();

        // Recrear con id incremental
        Schema::dropIfExists('excels');
        
        Schema::create('excels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->default('Hoja sin título');
            $table->json('data')->nullable()->comment('Datos de las celdas: {cellId: {value, formula, style}}');
            $table->json('last_cursor_position')->nullable()->comment('{row, col}');
            $table->json('column_widths')->nullable()->comment('{A: 120, B: 200, ...}');
            $table->json('row_heights')->nullable()->comment('{1: 25, 2: 50, ...}');
            $table->integer('frozen_rows')->default(0);
            $table->integer('frozen_columns')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Restaurar datos
        foreach ($existingData as $row) {
            DB::table('excels')->insert([
                'user_id' => $row->user_id,
                'client_id' => $row->client_id,
                'name' => $row->name,
                'data' => $row->data,
                'last_cursor_position' => $row->last_cursor_position,
                'column_widths' => $row->column_widths,
                'row_heights' => $row->row_heights,
                'frozen_rows' => $row->frozen_rows,
                'frozen_columns' => $row->frozen_columns,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }
};
