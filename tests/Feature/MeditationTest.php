<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeditationTest extends TestCase
{
    use RefreshDatabase;

    public function test_meditation_log_can_be_stored(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('meditation.store'), [
                'duration_seconds' => 300,
                'technique' => 'breathing',
                'breath_cycles_completed' => 37,
                'notes' => 'Felt calm.',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'duration_seconds' => 300,
        ]);

        $this->assertDatabaseHas('meditation_logs', [
            'user_id' => $user->id,
            'duration_seconds' => 300,
            'technique' => 'breathing',
            'breath_cycles_completed' => 37,
            'notes' => 'Felt calm.',
        ]);
    }

    public function test_meditation_log_requires_duration(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('meditation.store'), [
                'technique' => 'breathing',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('duration_seconds');
    }

    public function test_meditation_dashboard_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['selected_discipline' => 'meditation'])
            ->get(route('home'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_store_meditation_log(): void
    {
        $response = $this->postJson(route('meditation.store'), [
            'duration_seconds' => 120,
        ]);

        $response->assertUnauthorized();
    }
}
