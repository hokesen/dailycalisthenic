<?php

namespace Tests\Feature;

use App\Enums\LiftCategory;
use App\Enums\SessionStatus;
use App\Enums\TrainingDiscipline;
use App\Models\Exercise;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiftingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-11 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lifting_set_can_be_logged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('lifting.log-set'), [
                'lift_category' => LiftCategory::Bench->value,
                'weight_lbs' => 185,
                'reps_completed' => 5,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_personal_record' => true,
                'weight_lbs' => 185,
                'reps_completed' => 5,
            ]);

        $template = SessionTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', 'Lifting Log')
            ->first();

        $this->assertNotNull($template);
        $this->assertSame(TrainingDiscipline::Lifting, $template->discipline);

        $session = Session::query()
            ->where('user_id', $user->id)
            ->where('session_template_id', $template->id)
            ->first();

        $this->assertNotNull($session);
        $this->assertSame(SessionStatus::InProgress, $session->status);
        $this->assertEquals(now('UTC')->toIso8601String(), $session->started_at?->toIso8601String());

        $exercise = Exercise::query()
            ->where('user_id', $user->id)
            ->where('name', LiftCategory::Bench->label())
            ->where('discipline', TrainingDiscipline::Lifting->value)
            ->first();

        $this->assertNotNull($exercise);

        $this->assertDatabaseHas('session_exercises', [
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'lift_category' => LiftCategory::Bench->value,
            'reps_completed' => 5,
            'sets_completed' => 1,
            'is_personal_record' => true,
        ]);
    }

    public function test_lifting_set_detects_personal_record(): void
    {
        $user = User::factory()->create();

        $this->createLiftingEntry($user, LiftCategory::Deadlift, 225, 5);

        $response = $this
            ->actingAs($user)
            ->postJson(route('lifting.log-set'), [
                'lift_category' => LiftCategory::Deadlift->value,
                'weight_lbs' => 235,
                'reps_completed' => 3,
                'sets_completed' => 2,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('is_personal_record', true)
            ->assertJsonPath('weight_lbs', 235)
            ->assertJsonPath('reps_completed', 3);

        $this->assertDatabaseHas('session_exercises', [
            'lift_category' => LiftCategory::Deadlift->value,
            'weight_lbs' => 235,
            'reps_completed' => 3,
            'sets_completed' => 2,
            'is_personal_record' => true,
        ]);
    }

    public function test_lifting_set_requires_weight_and_reps(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('lifting.log-set'), [
                'lift_category' => LiftCategory::Squat->value,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['weight_lbs', 'reps_completed']);
    }

    public function test_lifting_dashboard_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['selected_discipline' => TrainingDiscipline::Lifting->value])
            ->get(route('home'));

        $response->assertOk();
        $response->assertSee('Personal Records');
        $response->assertSee('Practice Blocks');
        $response->assertSee('Recent Activity');
    }

    public function test_unauthenticated_user_cannot_log_lifting_set(): void
    {
        $response = $this->post(route('lifting.log-set'), [
            'lift_category' => LiftCategory::Bench->value,
            'weight_lbs' => 135,
            'reps_completed' => 5,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_lifting_pr_calculation_is_per_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createLiftingEntry($user, LiftCategory::Row, 165, 5);
        $this->createLiftingEntry($otherUser, LiftCategory::Row, 245, 5);

        $response = $this
            ->actingAs($user)
            ->postJson(route('lifting.log-set'), [
                'lift_category' => LiftCategory::Row->value,
                'weight_lbs' => 185,
                'reps_completed' => 5,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('is_personal_record', true);

        $this->assertDatabaseHas('session_exercises', [
            'lift_category' => LiftCategory::Row->value,
            'weight_lbs' => 185,
            'is_personal_record' => true,
        ]);
    }

    private function createLiftingEntry(
        User $user,
        LiftCategory $category,
        float $weightLbs,
        int $repsCompleted,
        int $setsCompleted = 1
    ): SessionExercise {
        $template = SessionTemplate::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Lifting Log',
                'discipline' => TrainingDiscipline::Lifting->value,
            ],
            [
                'default_rest_seconds' => 180,
                'is_public' => false,
            ]
        );

        $exercise = Exercise::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => $category->label(),
                'discipline' => TrainingDiscipline::Lifting->value,
            ],
            [
                'category' => $this->exerciseCategoryValueFor($category),
            ]
        );

        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'name' => 'Lifting Log',
            'status' => SessionStatus::Completed,
            'started_at' => now('UTC')->subDay(),
            'completed_at' => now('UTC')->subDay()->addHour(),
        ]);

        return SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
            'weight_lbs' => $weightLbs,
            'reps_completed' => $repsCompleted,
            'sets_completed' => $setsCompleted,
            'lift_category' => $category->value,
            'is_personal_record' => true,
            'started_at' => $session->started_at,
            'completed_at' => $session->completed_at,
        ]);
    }

    private function exerciseCategoryValueFor(LiftCategory $category): string
    {
        return match ($category->movementPattern()) {
            'push' => 'push',
            'pull' => 'pull',
            'legs' => 'legs',
            default => 'full_body',
        };
    }
}
