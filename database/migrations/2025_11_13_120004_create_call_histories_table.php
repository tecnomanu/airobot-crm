<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone')->index();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->timestamp('call_date')->index();
            $table->integer('duration_seconds')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->string('status'); // completed, no_answer, hung_up, failed, busy
            $table->uuid('lead_id')->nullable();
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            $table->string('provider')->nullable();
            $table->string('call_id_external')->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('recording_url')->nullable();
            $table->longText('transcript')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};

