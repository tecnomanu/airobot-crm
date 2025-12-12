<?php

namespace Database\Seeders;

use App\Enums\CallStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Models\Campaign\Campaign;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadCall;
use App\Models\Lead\LeadMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoLeadsSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = Campaign::first();
        $campaignId = $campaign?->id;

        $demoLeads = [
            [
                'name' => 'María García',
                'phone' => '+34612345678',
                'email' => 'maria.garcia@email.com',
                'source' => LeadSource::WHATSAPP,
                'status' => LeadStatus::PENDING,
                'option_selected' => '1',
                'messages' => [
                    ['direction' => 'inbound', 'content' => 'Hola, vi su anuncio sobre paneles solares. Me interesa saber más.'],
                    ['direction' => 'outbound', 'content' => 'Hola María! Gracias por contactarnos. Somos especialistas en instalación de paneles solares. ¿Tienes vivienda propia o alquilada?'],
                    ['direction' => 'inbound', 'content' => 'Es propia, un chalet de unos 150m2'],
                    ['direction' => 'outbound', 'content' => 'Perfecto! Un chalet de 150m2 es ideal para una instalación solar. ¿Cuánto pagas aproximadamente de luz al mes?'],
                ],
                'calls' => [],
            ],
            [
                'name' => 'Carlos Rodríguez',
                'phone' => '+34623456789',
                'email' => 'carlos.rodriguez@gmail.com',
                'source' => LeadSource::WEBHOOK_INICIAL,
                'status' => LeadStatus::IN_PROGRESS,
                'option_selected' => '2',
                'messages' => [
                    ['direction' => 'inbound', 'content' => 'Buenos días, quiero información sobre instalación fotovoltaica'],
                    ['direction' => 'outbound', 'content' => 'Buenos días Carlos! Encantados de ayudarte. ¿Es para una vivienda o para un negocio?'],
                    ['direction' => 'inbound', 'content' => 'Para mi casa, es un adosado'],
                    ['direction' => 'outbound', 'content' => '¡Genial! Los adosados suelen tener buen potencial solar. ¿Tu tejado está orientado hacia el sur?'],
                    ['direction' => 'inbound', 'content' => 'Sí, tiene buena orientación. Pago unos 120€ de luz'],
                    ['direction' => 'outbound', 'content' => 'Excelente! Con ese consumo podrías ahorrar hasta un 70%. Te paso con un asesor para una evaluación personalizada.'],
                ],
                'calls' => [
                    ['duration' => 180, 'status' => CallStatus::COMPLETED, 'notes' => 'Cliente interesado, solicita presupuesto detallado'],
                ],
            ],
            [
                'name' => 'Ana Martínez',
                'phone' => '+34634567890',
                'email' => 'ana.martinez@outlook.com',
                'source' => LeadSource::AGENTE_IA,
                'status' => LeadStatus::CONTACTED,
                'option_selected' => '1',
                'messages' => [
                    ['direction' => 'inbound', 'content' => 'Me gustaría recibir un presupuesto para mi vivienda'],
                    ['direction' => 'outbound', 'content' => 'Hola Ana! Con gusto te preparamos un presupuesto. ¿Podrías indicarme la dirección para verificar la viabilidad?'],
                    ['direction' => 'inbound', 'content' => 'Calle Mayor 45, Madrid. Es un piso en un edificio con tejado comunitario'],
                    ['direction' => 'outbound', 'content' => 'Entiendo. Para edificios comunitarios tenemos soluciones de autoconsumo compartido. ¿Ya han hablado los vecinos sobre esto?'],
                    ['direction' => 'inbound', 'content' => 'Sí, hay varios interesados. Somos 8 vecinos que queremos hacerlo juntos'],
                    ['direction' => 'outbound', 'content' => '¡Perfecto! El autoconsumo compartido es muy rentable. Un asesor te llamará para coordinar una reunión con los vecinos.'],
                ],
                'calls' => [
                    ['duration' => 245, 'status' => CallStatus::COMPLETED, 'notes' => 'Reunión programada con comunidad de vecinos para próxima semana'],
                    ['duration' => 120, 'status' => CallStatus::COMPLETED, 'notes' => 'Confirmación de cita, documentación necesaria enviada'],
                ],
            ],
            [
                'name' => 'Pedro Sánchez López',
                'phone' => '+34645678901',
                'email' => 'pedro.sanchez@empresa.es',
                'source' => LeadSource::WEBHOOK_INICIAL,
                'status' => LeadStatus::IN_PROGRESS,
                'option_selected' => '3',
                'messages' => [
                    ['direction' => 'inbound', 'content' => 'Busco instalación para mi empresa, nave industrial de 500m2'],
                    ['direction' => 'outbound', 'content' => 'Hola Pedro! Las instalaciones industriales son nuestra especialidad. ¿Cuál es el consumo mensual aproximado?'],
                    ['direction' => 'inbound', 'content' => 'Unos 2500€ al mes, tenemos maquinaria pesada'],
                    ['direction' => 'outbound', 'content' => 'Con ese consumo, una instalación de 80-100kW sería ideal. El retorno de inversión sería de aproximadamente 4-5 años.'],
                    ['direction' => 'inbound', 'content' => '¿Hay opciones de financiación?'],
                    ['direction' => 'outbound', 'content' => 'Sí, ofrecemos varias opciones: leasing, renting o financiación bancaria con condiciones preferentes. ¿Cuándo podemos agendar una visita técnica?'],
                ],
                'calls' => [
                    ['duration' => 320, 'status' => CallStatus::COMPLETED, 'notes' => 'Visita técnica agendada, interés alto en financiación mediante leasing'],
                ],
            ],
            [
                'name' => 'Laura Fernández',
                'phone' => '+34656789012',
                'email' => 'laura.fernandez@mail.com',
                'source' => LeadSource::MANUAL,
                'status' => LeadStatus::CONTACTED,
                'option_selected' => '2',
                'messages' => [
                    ['direction' => 'outbound', 'content' => 'Hola Laura! Te contactamos de SolarTech. Vimos que dejaste tus datos en nuestra web. ¿Sigues interesada en paneles solares?'],
                    ['direction' => 'inbound', 'content' => 'Sí, pero tengo dudas sobre si mi tejado es apto'],
                    ['direction' => 'outbound', 'content' => 'Es muy común esa duda. Hacemos un estudio gratuito con imágenes satelitales. ¿Me compartes tu dirección?'],
                    ['direction' => 'inbound', 'content' => 'Av. de la Constitución 78, Sevilla'],
                    ['direction' => 'outbound', 'content' => 'Acabo de revisar. Tu tejado tiene excelente orientación sur-oeste y sin sombras. Es ideal para solar.'],
                ],
                'calls' => [
                    ['duration' => 0, 'status' => CallStatus::NO_ANSWER, 'notes' => 'Sin respuesta, reintento programado'],
                    ['duration' => 185, 'status' => CallStatus::COMPLETED, 'notes' => 'Contacto exitoso, cliente pidió enviar información por email'],
                ],
            ],
            [
                'name' => 'Roberto Díaz',
                'phone' => '+34667890123',
                'email' => null,
                'source' => LeadSource::WHATSAPP,
                'status' => LeadStatus::PENDING,
                'option_selected' => '1',
                'messages' => [
                    ['direction' => 'inbound', 'content' => 'Hola buenas tardes'],
                    ['direction' => 'outbound', 'content' => '¡Buenas tardes! Gracias por contactar con SolarTech. ¿En qué podemos ayudarte?'],
                    ['direction' => 'inbound', 'content' => 'Quiero saber cuánto costaría poner placas en mi casa'],
                ],
                'calls' => [],
            ],
        ];

        foreach ($demoLeads as $leadData) {
            $lead = Lead::create([
                'id' => Str::uuid(),
                'phone' => $leadData['phone'],
                'name' => $leadData['name'],
                'source' => $leadData['source'],
                'status' => $leadData['status'],
                'option_selected' => $leadData['option_selected'],
                'campaign_id' => $campaignId,
                'created_at' => now()->subDays(rand(1, 14))->subHours(rand(1, 23)),
            ]);

            // Create messages with realistic timestamps
            $messageTime = $lead->created_at->copy();
            foreach ($leadData['messages'] as $msgData) {
                $messageTime = $messageTime->addMinutes(rand(1, 30));

                LeadMessage::create([
                    'id' => Str::uuid(),
                    'lead_id' => $lead->id,
                    'phone' => $lead->phone,
                    'direction' => $msgData['direction'] === 'inbound' ? MessageDirection::INBOUND : MessageDirection::OUTBOUND,
                    'channel' => MessageChannel::WHATSAPP,
                    'content' => $msgData['content'],
                    'status' => 'delivered',
                    'created_at' => $messageTime,
                    'updated_at' => $messageTime,
                ]);
            }

            // Create calls
            foreach ($leadData['calls'] as $callData) {
                $callTime = $messageTime->addHours(rand(1, 4));

                LeadCall::create([
                    'id' => Str::uuid(),
                    'lead_id' => $lead->id,
                    'campaign_id' => $campaignId,
                    'phone' => $lead->phone,
                    'call_date' => $callTime,
                    'duration_seconds' => $callData['duration'],
                    'status' => $callData['status'],
                    'notes' => $callData['notes'],
                    'provider' => 'retell',
                    'created_at' => $callTime,
                    'updated_at' => $callTime,
                ]);
            }
        }

        $this->command->info('Created 6 demo leads with messages and calls');
    }
}
