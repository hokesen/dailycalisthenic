<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_dark_mode_classes(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('class="dark"', false);
        $response->assertSee('dark:bg-gray-900', false);
        $response->assertSee('dark:bg-gray-800', false);
    }

    public function test_dashboard_has_dark_mode_classes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('class="dark"', false);
        $response->assertSee('dark:bg-gray-900', false);
        $response->assertSee('dark:bg-gray-800', false);
        $response->assertSee('dark:text-gray-200', false);
    }
}
