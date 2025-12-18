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

        // Determinar qué spreadsheet ID usar
        $spreadsheetId = null;
        $sheetName = null;

        if ($this->lead->intention === \App\Enums\LeadIntention::INTERESTED->value) {
            $spreadsheetId = $campaign->google_spreadsheet_id;
            $sheetName = $campaign->google_sheet_name;
        } elseif ($this->lead->intention === \App\Enums\LeadIntention::NOT_INTERESTED->value) {
            $spreadsheetId = $campaign->intention_not_interested_google_spreadsheet_id;
            $sheetName = $campaign->intention_not_interested_google_sheet_name;
        }

        // Si no hay spreadsheet configurado para este caso, salir
        if (!$campaign || !$campaign->google_integration_id || !$spreadsheetId) {
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

            // TODO: Support sheet name selection in appendRow if the service supports it
            // Currently appendRow takes spreadsheetId and optional range. 
            // If sheetName is provided, we should probably prepend it to range like "Sheet1!A1"
            $range = 'A1';
            if ($sheetName) {
                $range = "{$sheetName}!A1";
            }

            $sheetsService->appendRow($spreadsheetId, $data, $range);

            $this->lead->update(['exported_at' => now()]);

            Log::info("Lead {$this->lead->id} exported to Google Sheet {$spreadsheetId} (Intention: {$this->lead->intention})");
        } catch (\Exception $e) {
            Log::error("Failed to export lead {$this->lead->id} to Google Sheet: " . $e->getMessage());
            // Retry logic could be added here
        }
    }
}
