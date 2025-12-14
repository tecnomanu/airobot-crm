<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function main_routes_are_accessible_to_authenticated_users()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(), // Important for 'verified' middleware
        ]);

        $routes = [
            'dashboard',
            'profile.edit',
            'leads.index',
            'campaigns.index',
            'clients.index',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));
            
            // Should be 200 OK (not 404, 403, 500)
            $response->assertStatus(200);
        }
    }
}
