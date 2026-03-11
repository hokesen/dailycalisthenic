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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiftingTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifting_set_can_be_logged(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('lifting.log-set'), [
            'lift_category' => 'bench',
            'weight_lbs' => 135,
            'reps_completed' => 5,
            'sets_completed' => 3,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_personal_record' => true,
                'weight_lbs' => 135,
                'reps_completed' => 5,
            ]);

        $this->assertDatabaseHas('session_exercises', [
            'lift_category' => 'bench',
            'weight_lbs' => 135,
            'reps_completed' => 5,
            'sets_completed' => 3,
        ]);
    }

    public function test_lifting_set_detects_personal_record(): void
    {
        $user = User::factory()->create();

        $template = SessionTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Lifting Log',
            'discipline' => TrainingDiscipline::Lifting->value,
        ]);

        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::InProgress->value,
            'started_at' => now(),
        ]);

        $exercise = Exercise::factory()->create([
            'user_id' => $user->id,
            'name' => 'Bench Press',
            'discipline' => TrainingDiscipline::Lifting->value,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'lift_category' => LiftCategory::Bench->value,
            'weight_lbs' => 100,
            'reps_completed' => 5,
            'sets_completed' => 3,
        ]);

        $response = $this->actingAs($user)->postJson(route('lifting.log-set'), [
            'lift_category' => 'bench',
            'weight_lbs' => 150,
            'reps_completed' => 5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_personal_record' => true,
            ]);
    }

    public function test_lifting_set_requires_weight_and_reps(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('lifting.log-set'), [
            'lift_category' => 'bench',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weight_lbs', 'reps_completed']);
    }

    public function test_lifting_dashboard_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['selected_discipline' => 'lifting'])
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_log_lifting_set(): void
    {
        $response = $this->postJson(route('lifting.log-set'), [
            'lift_category' => 'bench',
            'weight_lbs' => 135,
            'reps_completed' => 5,
        ]);

        $response->assertUnauthorized();
    }

    public function test_lifting_pr_calculation_is_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $template = SessionTemplate::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Lifting Log',
            'discipline' => TrainingDiscipline::Lifting->value,
        ]);

        $session = Session::factory()->create([
            'user_id' => $userA->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::InProgress->value,
            'started_at' => now(),
        ]);

        $exercise = Exercise::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Squat',
            'discipline' => TrainingDiscipline::Lifting->value,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'lift_category' => LiftCategory::Squat->value,
            'weight_lbs' => 200,
            'reps_completed' => 5,
            'sets_completed' => 3,
        ]);

        $response = $this->actingAs($userB)->postJson(route('lifting.log-set'), [
            'lift_category' => 'squat',
            'weight_lbs' => 150,
            'reps_completed' => 5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_personal_record' => true,
            ]);

        $userAMaxWeight = SessionExercise::query()
            ->whereHas('session', fn ($q) => $q->where('user_id', $userA->id))
            ->where('lift_category', 'squat')
            ->max('weight_lbs');

        $this->assertEquals(200, (float) $userAMaxWeight);
    }
}
