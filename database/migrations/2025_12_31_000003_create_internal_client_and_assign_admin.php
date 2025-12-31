<?php

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Well-known UUID for the internal AirRobot HQ client.
     * Using a fixed UUID allows referencing it consistently across environments.
     */
    public const INTERNAL_CLIENT_ID = '00000000-0000-0000-0000-000000000001';

    public function up(): void
    {
        // Create internal client "AirRobot HQ"
        DB::table('clients')->insert([
            'id' => self::INTERNAL_CLIENT_ID,
            'name' => 'AirRobot HQ',
            'email' => 'admin@airobot.local',
            'company' => 'AirRobot',
            'status' => ClientStatus::ACTIVE->value,
            'type' => ClientType::INTERNAL->value,
            'notes' => 'Internal system client. Owner of platform-level resources.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign all admin users without client_id to the internal client
        DB::table('users')
            ->where('role', UserRole::ADMIN->value)
            ->whereNull('client_id')
            ->update(['client_id' => self::INTERNAL_CLIENT_ID]);

        // Backfill existing google_integrations: assign to user's client
        // If user has no client, assign to internal client
        $integrations = DB::table('google_integrations')
            ->whereNull('client_id')
            ->get();

        foreach ($integrations as $integration) {
            $user = DB::table('users')->find($integration->created_by_user_id);
            $clientId = $user?->client_id ?? self::INTERNAL_CLIENT_ID;

            DB::table('google_integrations')
                ->where('id', $integration->id)
                ->update(['client_id' => $clientId]);
        }
    }

    public function down(): void
    {
        // Unassign admin users from internal client
        DB::table('users')
            ->where('client_id', self::INTERNAL_CLIENT_ID)
            ->update(['client_id' => null]);

        // Clear client_id from integrations that were assigned to internal client
        DB::table('google_integrations')
            ->where('client_id', self::INTERNAL_CLIENT_ID)
            ->update(['client_id' => null]);

        // Delete internal client
        DB::table('clients')
            ->where('id', self::INTERNAL_CLIENT_ID)
            ->delete();
    }
};

