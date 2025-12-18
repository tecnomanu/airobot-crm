<?php

namespace App\Jobs;

use App\Models\Lead\Lead;
use App\Models\Integration\GoogleIntegration;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportLeadToGoogleSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
        //
    }

    public function handle(GoogleSheetsService $sheetsService): void
    {
        $campaign = $this->lead->campaign;
        
        if (!$campaign || !$campaign->google_integration_id || !$campaign->google_spreadsheet_id) {
            return;
        }

        try {
            $integration = GoogleIntegration::find($campaign->google_integration_id);
            if (!$integration) {
                Log::error("Google Integration not found for campaign {$campaign->id}");
                return;
            }

            $sheetsService->setIntegration($integration);

            $data = [
                $this->lead->created_at->format('Y-m-d H:i:s'),
                $this->lead->name,
                $this->lead->phone,
                $this->lead->email ?? '',
                $this->lead->city ?? '',
                $this->lead->status->value,
                $this->lead->intention ?? '',
                $this->lead->notes ?? '',
                // Add more fields as needed
            ];

            $sheetsService->appendRow($campaign->google_spreadsheet_id, $data);
            
            $this->lead->update(['exported_at' => now()]);

            Log::info("Lead {$this->lead->id} exported to Google Sheet {$campaign->google_spreadsheet_id}");

        } catch (\Exception $e) {
            Log::error("Failed to export lead {$this->lead->id} to Google Sheet: " . $e->getMessage());
            // Retry logic could be added here
        }
    }
}
