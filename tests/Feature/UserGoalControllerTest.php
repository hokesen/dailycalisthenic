<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\UserGoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGoalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_exercise_goals(): void
    {
        $user = User::factory()->create();
        $exercise1 = Exercise::factory()->create();
        $exercise2 = Exercise::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [$exercise1->id, $exercise2->id],
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'exercise_goals' => [$exercise1->id, $exercise2->id],
        ]);

        $this->assertDatabaseHas('user_goals', [
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $userGoal = $user->goals()->active()->first();
        $this->assertEquals([$exercise1->id, $exercise2->id], $userGoal->exercise_goals);
    }

    public function test_user_can_clear_exercise_goals(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->create();

        UserGoal::factory()->create([
            'user_id' => $user->id,
            'exercise_goals' => [$exercise->id],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [],
            ]);

        $response->assertOk();
        $userGoal = $user->goals()->active()->first();
        $this->assertEquals([], $userGoal->exercise_goals);
    }

    public function test_update_exercise_goals_creates_goal_if_none_exists(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->create();

        $this->assertEquals(0, $user->goals()->count());

        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [$exercise->id],
            ]);

        $response->assertOk();
        $this->assertEquals(1, $user->goals()->count());
    }

    public function test_update_exercise_goals_requires_authentication(): void
    {
        $response = $this->patchJson(route('user-goals.update-exercise-goals'), [
            'exercise_ids' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_can_update_exercise_goals_with_default_exercises(): void
    {
        $user = User::factory()->create();

        // Default exercises use negative IDs
        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [-1, -2, -3],
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'exercise_goals' => [-1, -2, -3],
        ]);
    }

    public function test_user_can_update_exercise_goals_with_mixed_exercises(): void
    {
        $user = User::factory()->create();
        $dbExercise = Exercise::factory()->create();

        // Mix of default (negative) and database (positive) IDs
        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [-1, $dbExercise->id, -2],
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'exercise_goals' => [-1, $dbExercise->id, -2],
        ]);
    }

    public function test_update_exercise_goals_rejects_invalid_database_ids(): void
    {
        $user = User::factory()->create();

        // Use a positive ID that doesn't exist in database
        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [99999],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Some exercise IDs are invalid',
        ]);
    }

    public function test_update_exercise_goals_deduplicates_by_name_preferring_database_ids(): void
    {
        $user = User::factory()->create();

        // Create a database exercise with a name that matches a default exercise
        // Default "Dead Bug" has ID -1043, let's create a database version
        $dbDeadBug = Exercise::factory()->create([
            'user_id' => null,
            'name' => 'Dead Bug',
        ]);

        // Submit goals with both the default ID (-1043) and database ID
        // The controller should deduplicate and keep only the database version
        $response = $this->actingAs($user)
            ->patchJson(route('user-goals.update-exercise-goals'), [
                'exercise_ids' => [-1043, $dbDeadBug->id, -1024], // -1043 is default Dead Bug
            ]);

        $response->assertOk();

        $userGoal = $user->goals()->active()->first();
        $savedIds = $userGoal->exercise_goals;

        // Should only have 2 exercises: the database Dead Bug and the other default
        $this->assertCount(2, $savedIds);
        // Should contain the database Dead Bug ID
        $this->assertContains($dbDeadBug->id, $savedIds);
        // Should contain the other default exercise
        $this->assertContains(-1024, $savedIds);
        // Should NOT contain the default Dead Bug ID
        $this->assertNotContains(-1043, $savedIds);
    }
}
