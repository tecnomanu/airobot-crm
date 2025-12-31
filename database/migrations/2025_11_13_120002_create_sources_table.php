<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // Maps to SourceType enum
            $table->json('config'); // Type-specific configuration
            $table->string('status')->default('pending_setup'); // Maps to SourceStatus enum
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes for query optimization
            $table->index('type');
            $table->index('status');
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'status']);
        });

        // Add FK from clients to sources for default_whatsapp_source_id
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('default_whatsapp_source_id')
                ->references('id')
                ->on('sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['default_whatsapp_source_id']);
        });
        Schema::dropIfExists('sources');
    }
};
