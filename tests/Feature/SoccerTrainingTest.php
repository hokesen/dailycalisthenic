<?php

namespace Tests\Feature;

use App\Enums\TrainingDiscipline;
use App\Models\AssessmentResult;
use App\Models\TrainingProgramEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoccerTrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_discipline_switcher_persists_soccer_selection_and_renders_soccer_sections(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('discipline.update'), [
                'discipline' => TrainingDiscipline::Soccer->value,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('selected_discipline', TrainingDiscipline::Soccer->value);

        $dashboardResponse = $this
            ->actingAs($user)
            ->withSession(['selected_discipline' => TrainingDiscipline::Soccer->value])
            ->get('/');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Program Library');
        $dashboardResponse->assertSee('Practice Library');
        $dashboardResponse->assertSee('Progress &amp; Benchmarks', false);
    }

    public function test_soccer_new_user_dashboard_shows_quick_start_onboarding(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['selected_discipline' => TrainingDiscipline::Soccer->value])
            ->get('/');

        $response->assertOk();
        $response->assertSee('Start Here');
        $response->assertSee('Team Schedule Track');
        $response->assertSee('Start Stanford Track');
        $response->assertSee('6-Week Conditioning Block');
        $response->assertSee('Baseline Benchmarks');
        $response->assertSee('What To Expect');
    }

    public function test_user_can_enroll_in_code_defined_soccer_program(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('training-programs.store'), [
                'program_slug' => 'pro-soccer-fitness-6-week',
                'starts_on' => '2026-03-10',
            ]);

        $response->assertRedirect(route('home', ['tab' => 'templates']));
        $enrollment = TrainingProgramEnrollment::query()->firstOrFail();

        $this->assertSame($user->id, $enrollment->user_id);
        $this->assertSame('pro-soccer-fitness-6-week', $enrollment->program_slug);
        $this->assertSame('2026-03-10', $enrollment->starts_on?->toDateString());
        $this->assertTrue($enrollment->is_active);
    }

    public function test_starting_soccer_program_sets_specific_success_message(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['selected_discipline' => TrainingDiscipline::Soccer->value])
            ->post(route('training-programs.store'), [
                'program_slug' => 'pro-soccer-fitness-6-week',
                'starts_on' => '2026-03-10',
            ]);

        $response->assertRedirect(route('home', ['tab' => 'templates']));
        $response->assertSessionHas('success', fn (string $message) => str_contains($message, 'Started Pro Soccer Fitness 6-Week Program.'));
    }

    public function test_user_can_record_soccer_assessment_results(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('assessment-results.store'), [
                'assessment_slug' => 'national-team-fitness-test',
                'recorded_on' => '2026-03-10',
                'results' => [
                    'first_1200' => '4:40',
                    'second_1200' => '4:10',
                    'two_k' => '7:35',
                    'split_times' => "200=0:47\n400=1:35",
                ],
                'notes' => 'Strong finish',
            ]);

        $response->assertRedirect(route('home', ['tab' => 'progress']));
        $result = AssessmentResult::query()->firstOrFail();

        $this->assertSame($user->id, $result->user_id);
        $this->assertSame('national-team-fitness-test', $result->assessment_slug);
        $this->assertSame('2026-03-10', $result->recorded_on?->toDateString());
        $this->assertSame('pass', $result->derived_status);
        $this->assertSame(280, $result->results['first_1200']);
        $this->assertSame(250, $result->results['second_1200']);
        $this->assertSame(['200=0:47', '400=1:35'], $result->split_results['split_times']);
    }

    public function test_go_route_supports_catalog_template_slug_and_materializes_soccer_template(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('go.index', [
                'template_slug' => 'soccer-sr22-22',
                'discipline' => TrainingDiscipline::Soccer->value,
            ]));

        $response->assertOk();
        $response->assertSee('Warm-Up');
        $response->assertSee('Set 1');

        $this->assertDatabaseHas('session_templates', [
            'name' => 'Soccer - SR22 (22yds)',
            'discipline' => TrainingDiscipline::Soccer->value,
        ]);

        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
            'name' => 'Soccer - SR22 (22yds)',
            'status' => 'planned',
        ]);

        $this->assertDatabaseCount('practice_blocks', 7);
        $this->assertDatabaseCount('session_exercises', 5);
    }

    public function test_catalog_practice_can_be_linked_to_program_enrollment_and_day_log(): void
    {
        $user = User::factory()->create();
        $enrollment = TrainingProgramEnrollment::query()->create([
            'user_id' => $user->id,
            'program_slug' => 'pro-soccer-fitness-6-week',
            'starts_on' => '2026-03-10',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('go.index', [
                'template_slug' => 'soccer-sr22-22',
                'discipline' => TrainingDiscipline::Soccer->value,
                'program_enrollment' => $enrollment->id,
                'program_day_key' => 'w1-d3',
                'scheduled_for' => '2026-03-12',
            ]));

        $response->assertOk();

        $this->assertDatabaseHas('sessions', [
            'training_program_enrollment_id' => $enrollment->id,
            'program_day_key' => 'w1-d3',
        ]);

        $dayLog = $enrollment->dayLogs()->firstOrFail();

        $this->assertSame('w1-d3', $dayLog->program_day_key);
        $this->assertSame('2026-03-12', $dayLog->scheduled_for?->toDateString());
        $this->assertSame('pending', $dayLog->status?->value);
    }
}
