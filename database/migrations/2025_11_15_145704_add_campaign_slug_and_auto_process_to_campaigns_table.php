<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Slug único para identificar campañas fácilmente
            $table->string('campaign_slug')->nullable()->unique()->after('match_pattern');

            // Checkbox para activar/desactivar procesamiento automático
            $table->boolean('auto_process_enabled')->default(true)->after('campaign_slug');

            $table->index('campaign_slug');
        });

        // Generar slugs para campañas existentes
        $campaigns = \App\Models\Campaign::all();
        foreach ($campaigns as $campaign) {
            $baseSlug = \Illuminate\Support\Str::slug($campaign->name);
            $slug = $baseSlug;
            $counter = 1;

            // Asegurar que el slug sea único
            while (\App\Models\Campaign::where('campaign_slug', $slug)->where('id', '!=', $campaign->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $campaign->update(['campaign_slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['campaign_slug']);
            $table->dropColumn(['campaign_slug', 'auto_process_enabled']);
        });
    }
};
