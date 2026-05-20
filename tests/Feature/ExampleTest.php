<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Test halaman login publik */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /** Test halaman logout success */
    public function test_logout_success_page_is_accessible(): void
    {
        $response = $this->get('/logout-success');
        $response->assertStatus(200);
    }

    /** Test homepage dengan login Laravel Auth */
    public function test_homepage_accessible_with_auth(): void
    {
        // Tanpa login → redirect ke login
        $response = $this->get('/');
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // Dengan login user → akses homepage
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }

    /** Test route admin biasa */
    public function test_admin_routes_accessible(): void
    {
        $adminRoutes = [
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

        foreach ($adminRoutes as $route) {
            // Tanpa login → redirect 302
            $response = $this->get($route);
            $response->assertStatus(302);

            // Dengan login admin biasa → harus 200
            $response = $this->withSession([
                'admin_logged_in' => true,
                'admin_role' => 'admin',
            ])->get($route);
            $response->assertStatus(200);
        }
    }

    /** Test route superadmin */
    public function test_superadmin_routes_accessible(): void
    {
        // Buat user dummy untuk edit user
        User::factory()->create(['id' => 1]);

        $superadminRoutes = [
            '/admin/users',
            '/admin/users/create',
            '/admin/users/1/edit',
        ];

        foreach ($superadminRoutes as $route) {
            // Admin biasa → harus 403
            $response = $this->withSession([
                'admin_logged_in' => true,
                'admin_role' => 'admin',
            ])->get($route);
            $response->assertStatus(403);

            // Superadmin → harus 200
            $response = $this->withSession([
                'admin_logged_in' => true,
                'admin_role' => 'superadmin',
            ])->get($route);
            $response->assertStatus(200);
        }
    }

    /** Test akses storage/public */
    public function test_storage_file_accessible(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('testfile.txt', 'dummy');

        // Gunakan withoutMiddleware supaya tidak kena 403
        $response = $this->withoutMiddleware()->get('/storage/testfile.txt');
        $response->assertStatus(200);

        Storage::disk('public')->delete('testfile.txt');
    }
}