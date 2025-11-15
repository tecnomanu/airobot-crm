<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebhookConfigController extends Controller
{
    public function index()
    {
        $webhookUrl = route('webhooks.lead');
        
        $examplePayload = [
            'name' => 'webhook_register_phone',
            'args' => [
                'phone' => '2215648523',
                'name' => 'Juan',
                'city' => 'La Plata',
                'option_selected' => 'Plan A',
                'campaign' => 'direct-tv',
                'tags' => ['tag1', 'tag2'],
            ],
        ];

        $requiredFields = [
            ['field' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Número de teléfono del lead'],
            ['field' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Nombre del lead'],
            ['field' => 'city', 'type' => 'string', 'required' => false, 'description' => 'Ciudad del lead'],
            ['field' => 'option_selected', 'type' => 'string', 'required' => false, 'description' => 'Opción seleccionada por el lead'],
            ['field' => 'campaign', 'type' => 'string', 'required' => false, 'description' => 'ID o pattern de la campaña'],
            ['field' => 'tags', 'type' => 'array', 'required' => false, 'description' => 'Etiquetas asociadas al lead'],
        ];

        return Inertia::render('Webhook/Index', [
            'webhookUrl' => $webhookUrl,
            'examplePayload' => $examplePayload,
            'requiredFields' => $requiredFields,
        ]);
    }
}
