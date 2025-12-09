<?php

namespace Database\Seeders;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignWhatsappTemplate;
use Illuminate\Database\Seeder;

class CampaignWhatsappTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaign = Campaign::first();

        if (! $campaign) {
            $this->command->warn('⚠️  No hay campañas. Ejecuta DatabaseSeeder primero.');

            return;
        }

        // Plantilla de bienvenida
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'welcome',
            'name' => 'Bienvenida',
            'body' => "¡Hola {{name}}! 👋\n\nGracias por tu interés en {{campaign}}.\n\n¿En qué podemos ayudarte hoy?",
            'is_default' => true,
        ]);

        // Plantilla de seguimiento
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'followup',
            'name' => 'Seguimiento',
            'body' => "Hola {{name}}, te contactamos nuevamente sobre {{campaign}}.\n\n¿Tuviste oportunidad de revisar la información que te enviamos?",
            'is_default' => false,
        ]);

        // Plantilla de información
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'option_2_send_info',
            'name' => 'Envío de Información',
            'body' => "Hola {{name}}, como solicitaste, aquí está la información sobre {{campaign}}:\n\n✅ Beneficio 1\n✅ Beneficio 2\n✅ Beneficio 3\n\n¿Te gustaría agendar una llamada?",
            'is_default' => false,
        ]);

        // Plantilla de catálogo
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'option_i_brochure',
            'name' => 'Catálogo de Productos',
            'body' => "¡Aquí está nuestro catálogo {{name}}! 📋\n\nEncuentra todos nuestros productos y servicios.\n\nSi tienes dudas, estamos aquí para ayudarte.",
            'is_default' => false,
        ]);

        // Plantilla para cita agendada
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'option_1_appointment',
            'name' => 'Confirmación de Cita',
            'body' => "¡Perfecto {{name}}! ✅\n\nHemos agendado tu cita para {{campaign}}.\n\nTe enviaremos un recordatorio un día antes.\n\n¡Gracias por confiar en nosotros!",
            'is_default' => false,
        ]);

        // Plantilla borrador (no default)
        CampaignWhatsappTemplate::create([
            'campaign_id' => $campaign->id,
            'code' => 'draft_example',
            'name' => 'Plantilla Borrador',
            'body' => 'Esta es una plantilla en borrador que aún no está lista para usar.',
            'is_default' => false,
        ]);

        $this->command->info('✅ Plantillas de WhatsApp creadas exitosamente');
    }
}
