<?php

namespace App\Http\Controllers\Web\Message;

use App\Enums\MessageDirection;
use App\Http\Controllers\Controller;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadMessage;
use App\Services\Lead\LeadMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MessagesController extends Controller
{
    public function __construct(
        private LeadMessageService $messageService
    ) {}

    /**
     * Display the messages inbox with conversations list
     */
    public function index(Request $request): Response
    {
        $selectedLeadId = $request->input('lead_id');
        $search = $request->input('search');

        // Get leads that have messages, grouped by lead
        $leadsWithMessages = Lead::whereHas('messages')
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }, 'campaign.client'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount('messages')
            ->orderByDesc(
                LeadMessage::select('created_at')
                    ->whereColumn('lead_id', 'leads.id')
                    ->latest()
                    ->limit(1)
            )
            ->paginate(50);

        // Transform leads for the conversation list
        $conversations = $leadsWithMessages->through(function ($lead) {
            $lastMessage = $lead->messages->first();
            return [
                'id' => $lead->id,
                'name' => $lead->name ?: 'Unknown User',
                'phone' => $lead->phone,
                'last_message' => $lastMessage?->content,
                'last_message_time' => $lastMessage?->created_at?->toIso8601String(),
                'source' => $lead->source,
                'source_label' => $this->getSourceLabel($lead->source),
                'messages_count' => $lead->messages_count,
                'status' => $lead->status?->value,
                'ai_active' => $lead->ai_agent_active ?? true, // Default AI is driving
            ];
        });

        // Get selected conversation messages if lead_id provided
        $selectedConversation = null;
        $messages = [];

        if ($selectedLeadId) {
            $selectedLead = Lead::with(['campaign.client'])->find($selectedLeadId);
            
            if ($selectedLead) {
                $selectedConversation = [
                    'id' => $selectedLead->id,
                    'name' => $selectedLead->name ?: 'Unknown User',
                    'phone' => $selectedLead->phone,
                    'source' => $selectedLead->source,
                    'source_label' => $this->getSourceLabel($selectedLead->source),
                    'status' => $selectedLead->status?->value,
                    'ai_active' => $selectedLead->ai_agent_active ?? true,
                    'campaign' => $selectedLead->campaign ? [
                        'id' => $selectedLead->campaign->id,
                        'name' => $selectedLead->campaign->name,
                    ] : null,
                ];

                $messages = LeadMessage::where('lead_id', $selectedLeadId)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'direction' => $message->direction->value,
                            'channel' => $message->channel->value,
                            'status' => $message->status?->value,
                            'created_at' => $message->created_at->toIso8601String(),
                            'is_from_lead' => $message->direction === MessageDirection::INBOUND,
                        ];
                    });
            }
        }

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
            'filters' => [
                'search' => $search,
                'lead_id' => $selectedLeadId,
            ],
        ]);
    }

    /**
     * Get messages for a specific lead (API endpoint for real-time updates)
     */
    public function getMessages(string $leadId): JsonResponse
    {
        $messages = LeadMessage::where('lead_id', $leadId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'direction' => $message->direction->value,
                    'channel' => $message->channel->value,
                    'status' => $message->status?->value,
                    'created_at' => $message->created_at->toIso8601String(),
                    'is_from_lead' => $message->direction === MessageDirection::INBOUND,
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Send a message to a lead
     */
    public function sendMessage(Request $request, string $leadId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:4096',
        ]);

        $lead = Lead::findOrFail($leadId);

        $message = $this->messageService->create([
            'lead_id' => $leadId,
            'campaign_id' => $lead->campaign_id,
            'content' => $request->input('content'),
            'direction' => MessageDirection::OUTBOUND,
            'phone' => $lead->phone,
            'created_by' => Auth::id(),
        ]);

        // TODO: Integrate with WhatsApp API to actually send the message

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'direction' => $message->direction->value,
                'channel' => $message->channel->value,
                'status' => $message->status?->value,
                'created_at' => $message->created_at->toIso8601String(),
                'is_from_lead' => false,
            ],
        ]);
    }

    /**
     * Toggle AI agent status for a lead
     */
    public function toggleAiAgent(string $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        
        // Toggle AI status (if field doesn't exist, default behavior)
        $currentStatus = $lead->ai_agent_active ?? true;
        $lead->ai_agent_active = !$currentStatus;
        $lead->save();

        return response()->json([
            'success' => true,
            'ai_active' => $lead->ai_agent_active,
        ]);
    }

    /**
     * Get source label for display
     */
    private function getSourceLabel(?string $source): string
    {
        if (!$source) {
            return 'Unknown';
        }

        $sourceMap = [
            'webhook_inicial' => 'WEBHOOK',
            'webhook_event' => 'WEBHOOK',
            'whatsapp' => 'WHATSAPP',
            'ivr' => 'IVR',
            'csv' => 'CSV',
            'manual' => 'MANUAL',
            'landing_page' => 'LANDING',
            'facebook' => 'FACEBOOK',
            'google_ads' => 'GOOGLE',
        ];

        return $sourceMap[$source] ?? strtoupper($source);
    }
}




