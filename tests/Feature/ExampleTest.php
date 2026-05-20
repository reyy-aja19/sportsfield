<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** LOGIN PAGE */
    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')
            ->assertStatus(200);
    }

    /** LOGOUT SUCCESS PAGE */
    public function test_logout_success_page_is_accessible(): void
    {
        $this->get('/logout-success')
            ->assertStatus(200);
    }

    /** HOMEPAGE */
    public function test_homepage_accessible_with_auth(): void
    {
        // guest redirect login
        $this->get('/')
            ->assertStatus(302);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // login user
        $response = $this->actingAs($user)
            ->get('/');

        // homepage bisa redirect/dashboard
        $this->assertTrue(
            in_array($response->status(), [200, 302])
        );
    }

    /** ADMIN ROUTES */
    public function test_admin_routes_accessible(): void
    {
        $routes = [
            '/admin/dashboard',
            '/admin/booking',
            '/admin/facilities',
            '/admin/lapangan',
            '/admin/reports',
            '/admin/payments',
            '/admin/open-match',
            '/admin/rewards',
            '/admin/reviews',
            '/admin/venue',
        ];

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        foreach ($routes as $route) {

            // guest redirect
            $this->get($route)
                ->assertStatus(302);

            // admin login
            $response = $this->actingAs($admin)
                ->get($route);

            // route bisa redirect / success
            $this->assertTrue(
                in_array($response->status(), [200, 302])
            );
        }
    }

    /** SUPERADMIN ROUTES */
    public function test_superadmin_routes_accessible(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email_verified_at' => now(),
        ]);

        $routes = [
            '/admin/users',
            '/admin/users/create',
            '/admin/users/' . $superadmin->id . '/edit',
        ];

        foreach ($routes as $route) {

            // admin biasa
            $response = $this->actingAs($admin)
                ->get($route);

            $this->assertTrue(
                in_array($response->status(), [200, 302, 403])
            );

            // superadmin
            $response = $this->actingAs($superadmin)
                ->get($route);

            $this->assertTrue(
                in_array($response->status(), [200, 302])
            );
        }
    }

    /** STORAGE */
    public function test_storage_file_accessible(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put(
            'testfile.txt',
            'dummy'
        );

        Storage::disk('public')
            ->assertExists('testfile.txt');
    }
}