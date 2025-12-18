<?php

namespace App\Services;

use App\Models\Integration\GoogleIntegration;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\SpreadsheetProperties;

class GoogleSheetsService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
    }

    public function setIntegration(GoogleIntegration $integration)
    {
        $this->client->setAccessToken($integration->access_token);

        if ($this->client->isAccessTokenExpired()) {
            if ($integration->refresh_token) {
                $token = $this->client->fetchAccessTokenWithRefreshToken($integration->refresh_token);
                $integration->update([
                    'access_token' => $token['access_token'],
                    'expires_in' => $token['expires_in'],
                ]);
            } else {
                throw new \Exception('Token expired and no refresh token available');
            }
        }
    }

    public function createSpreadsheet(string $title): string
    {
        $service = new Sheets($this->client);
        $spreadsheet = new Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);

        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);

        return $spreadsheet->spreadsheetId;
    }

    public function appendRow(string $spreadsheetId, array $row, string $range = 'A1')
    {
        $service = new Sheets($this->client);
        $valueRange = new ValueRange([
            'values' => [$row]
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED'
        ];

        return $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $params);
    }
}
