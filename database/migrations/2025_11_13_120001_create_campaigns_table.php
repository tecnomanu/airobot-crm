<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, paused
            $table->string('strategy_type')->default('dynamic')
                ->comment('Strategy type: direct (linear execution), dynamic (conditional based on option_selected)');
            $table->string('export_rule')->default('interested_only')
                ->comment('Export rule: interested_only, not_interested_only, both, none');
            $table->string('match_pattern')->nullable()->unique();
            $table->string('slug')->nullable()->unique();
            $table->boolean('auto_process_enabled')->default(true);
            $table->string('country', 2)->default('AR')
                ->comment('ISO2 country code for the campaign');

            // Client defaults inheritance
            $table->boolean('use_client_call_defaults')->default(true)
                ->comment('If true, use client default_call_agent when campaign has none');
            $table->boolean('use_client_whatsapp_defaults')->default(true)
                ->comment('If true, use client default_whatsapp_source when campaign has none');

            // No response configuration
            $table->boolean('no_response_action_enabled')->default(false)
                ->comment('Enable auto-close on no response');
            $table->unsignedTinyInteger('no_response_max_attempts')->default(3)
                ->comment('Max attempts before marking as no response');
            $table->unsignedSmallInteger('no_response_timeout_hours')->default(48)
                ->comment('Hours to wait before auto-closing');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('client_id');
            $table->index('strategy_type');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
