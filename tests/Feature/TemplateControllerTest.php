<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_swap_exercise_in_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise1 = Exercise::factory()->create();
        $exercise2 = Exercise::factory()->create();

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.swap-exercise', $template), [
                'exercise_id' => $exercise1->id,
                'order' => 1,
                'new_exercise_id' => $exercise2->id,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise1->id,
        ]);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise2->id,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);
    }

    public function test_swapping_exercise_in_system_template_creates_copy(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null, 'name' => 'Beginner Workout']);
        $exercise1 = Exercise::factory()->create();
        $exercise2 = Exercise::factory()->create();

        $systemTemplate->exercises()->attach($exercise1->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.swap-exercise', $systemTemplate), [
                'exercise_id' => $exercise1->id,
                'order' => 1,
                'new_exercise_id' => $exercise2->id,
            ]);

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertEquals("John Doe's Beginner Workout", $userTemplate->name);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $userTemplate->id,
            'exercise_id' => $exercise2->id,
        ]);
    }

    public function test_swapping_duplicate_exercise_swaps_correct_occurrence(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);
        $exercise3 = Exercise::factory()->create(['name' => 'Pull-ups']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);
        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'sets' => 4,
            'reps' => 12,
        ]);
        $template->exercises()->attach($exercise1->id, [
            'order' => 3,
            'sets' => 5,
            'reps' => 15,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.swap-exercise', $template), [
                'exercise_id' => $exercise1->id,
                'order' => 3,
                'new_exercise_id' => $exercise3->id,
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise1->id,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise2->id,
            'order' => 2,
        ]);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise3->id,
            'order' => 3,
            'sets' => 5,
            'reps' => 15,
        ]);
        $this->assertDatabaseMissing('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise1->id,
            'order' => 3,
        ]);
    }

    public function test_user_can_remove_exercise_from_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise1 = Exercise::factory()->create();
        $exercise2 = Exercise::factory()->create();

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);

        $response = $this
            ->actingAs($user)
            ->delete(route('templates.remove-exercise', $template), [
                'exercise_id' => $exercise1->id,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise1->id,
        ]);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise2->id,
            'order' => 1,
        ]);
    }

    public function test_user_can_add_exercise_to_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'user_id' => $user->id,
            'default_rest_seconds' => 60,
        ]);
        $exercise = Exercise::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('templates.add-exercise', $template), [
                'exercise_id' => $exercise->id,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
            'rest_after_seconds' => 60,
        ]);
    }

    public function test_adding_exercise_to_system_template_creates_copy(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);
        $exercise = Exercise::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('templates.add-exercise', $systemTemplate), [
                'exercise_id' => $exercise->id,
            ]);

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $userTemplate->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    public function test_user_can_update_exercise_details_in_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.update-exercise', $template), [
                'exercise_id' => $exercise->id,
                'sets' => 4,
                'reps' => 12,
                'duration_seconds' => 90,
                'rest_after_seconds' => 45,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise->id,
            'sets' => 4,
            'reps' => 12,
            'duration_seconds' => 90,
            'rest_after_seconds' => 45,
        ]);
    }

    public function test_updating_exercise_in_system_template_creates_copy(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null]);
        $exercise = Exercise::factory()->create();

        $systemTemplate->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.update-exercise', $systemTemplate), [
                'exercise_id' => $exercise->id,
                'sets' => 5,
                'reps' => 15,
            ]);

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $userTemplate->id,
            'exercise_id' => $exercise->id,
            'sets' => 5,
            'reps' => 15,
        ]);
    }

    public function test_template_operations_require_authentication(): void
    {
        $template = SessionTemplate::factory()->create();
        $exercise = Exercise::factory()->create();

        $this->post(route('templates.swap-exercise', $template))->assertRedirect('/login');
        $this->delete(route('templates.remove-exercise', $template))->assertRedirect('/login');
        $this->post(route('templates.add-exercise', $template))->assertRedirect('/login');
        $this->patch(route('templates.update-exercise', $template))->assertRedirect('/login');
        $this->patch(route('templates.update-name', $template))->assertRedirect('/login');
        $this->post(route('templates.copy', $template))->assertRedirect('/login');
        $this->delete(route('templates.destroy', $template))->assertRedirect('/login');
    }

    public function test_user_can_edit_own_template_name(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.update-name', $template), [
                'name' => 'New Name',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('session_templates', [
            'id' => $template->id,
            'name' => 'New Name',
        ]);
    }

    public function test_editing_system_template_name_creates_copy(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null, 'name' => 'System Template']);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.update-name', $systemTemplate), [
                'name' => 'My Custom Template',
            ]);

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertEquals('My Custom Template', $userTemplate->name);

        $this->assertDatabaseHas('session_templates', [
            'id' => $systemTemplate->id,
            'name' => 'System Template',
        ]);
    }

    public function test_user_can_add_custom_exercise_to_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'user_id' => $user->id,
            'default_rest_seconds' => 60,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.add-custom-exercise', $template), [
                'name' => 'My Custom Exercise',
            ]);

        $response->assertRedirect(route('dashboard'));

        $exercise = Exercise::where('name', 'My Custom Exercise')->first();
        $this->assertNotNull($exercise);
        $this->assertEquals($user->id, $exercise->user_id);

        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
            'rest_after_seconds' => 60,
        ]);
    }

    public function test_adding_custom_exercise_to_system_template_creates_copy(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $systemTemplate = SessionTemplate::factory()->create([
            'user_id' => null,
            'name' => 'System Workout',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.add-custom-exercise', $systemTemplate), [
                'name' => 'My Custom Exercise',
            ]);

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertEquals("John Doe's System Workout", $userTemplate->name);

        $exercise = Exercise::where('name', 'My Custom Exercise')->first();
        $this->assertNotNull($exercise);
        $this->assertEquals($user->id, $exercise->user_id);

        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $userTemplate->id,
            'exercise_id' => $exercise->id,
        ]);
    }

    public function test_custom_exercise_name_is_required(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.add-custom-exercise', $template), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_adding_custom_exercise_requires_authentication(): void
    {
        $template = SessionTemplate::factory()->create();

        $this->post(route('templates.add-custom-exercise', $template))->assertRedirect('/login');
    }

    public function test_user_can_delete_own_template(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'My Template']);

        $response = $this
            ->actingAs($user)
            ->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('session_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_user_cannot_delete_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null, 'name' => 'System Template']);

        $response = $this
            ->actingAs($user)
            ->delete(route('templates.destroy', $systemTemplate));

        $response->assertForbidden();
        $this->assertDatabaseHas('session_templates', [
            'id' => $systemTemplate->id,
            'name' => 'System Template',
        ]);
    }

    public function test_user_cannot_delete_another_users_template(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user1->id, 'name' => 'User 1 Template']);

        $response = $this
            ->actingAs($user2)
            ->delete(route('templates.destroy', $template));

        $response->assertForbidden();
        $this->assertDatabaseHas('session_templates', [
            'id' => $template->id,
            'name' => 'User 1 Template',
        ]);
    }

    public function test_deleting_template_requires_authentication(): void
    {
        $template = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->delete(route('templates.destroy', $template))->assertRedirect('/login');
    }

    public function test_deleting_template_also_removes_associated_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
        ]);

        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $template->id,
            'exercise_id' => $exercise->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('session_template_exercises', [
            'session_template_id' => $template->id,
        ]);
    }

    public function test_user_can_copy_system_template(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $systemTemplate = SessionTemplate::factory()->create(['user_id' => null, 'name' => 'Beginner Workout']);
        $exercise = Exercise::factory()->create();

        $systemTemplate->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.copy', $systemTemplate));

        $response->assertRedirect(route('dashboard'));

        $userTemplate = SessionTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($userTemplate);
        $this->assertEquals("John Doe's Beginner Workout", $userTemplate->name);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $userTemplate->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
        ]);
    }

    public function test_user_can_copy_another_users_template(): void
    {
        $user1 = User::factory()->create(['name' => 'Jane Smith']);
        $user2 = User::factory()->create(['name' => 'John Doe']);
        $user1Template = SessionTemplate::factory()->create(['user_id' => $user1->id, 'name' => 'Advanced Workout']);
        $exercise = Exercise::factory()->create();

        $user1Template->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 5,
            'reps' => 15,
        ]);

        $response = $this
            ->actingAs($user2)
            ->post(route('templates.copy', $user1Template));

        $response->assertRedirect(route('dashboard'));

        $user2Template = SessionTemplate::where('user_id', $user2->id)->first();
        $this->assertNotNull($user2Template);
        $this->assertEquals("John Doe's Advanced Workout", $user2Template->name);
        $this->assertDatabaseHas('session_template_exercises', [
            'session_template_id' => $user2Template->id,
            'exercise_id' => $exercise->id,
            'sets' => 5,
            'reps' => 15,
        ]);
    }

    public function test_copying_own_template_creates_duplicate(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $template = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'My Workout']);

        $response = $this
            ->actingAs($user)
            ->post(route('templates.copy', $template));

        $response->assertRedirect(route('dashboard'));

        $this->assertEquals(2, SessionTemplate::where('user_id', $user->id)->count());
        $copiedTemplate = SessionTemplate::where('user_id', $user->id)
            ->where('name', "John Doe's My Workout")
            ->first();
        $this->assertNotNull($copiedTemplate);
    }

    public function test_user_can_toggle_template_visibility_to_public(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.toggle-visibility', $template));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('session_templates', [
            'id' => $template->id,
            'is_public' => true,
        ]);
    }

    public function test_user_can_toggle_template_visibility_to_private(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.toggle-visibility', $template));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('session_templates', [
            'id' => $template->id,
            'is_public' => false,
        ]);
    }

    public function test_user_cannot_toggle_visibility_of_another_users_template(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'user_id' => $user1->id,
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($user2)
            ->patch(route('templates.toggle-visibility', $template));

        $response->assertForbidden();
        $this->assertDatabaseHas('session_templates', [
            'id' => $template->id,
            'is_public' => false,
        ]);
    }

    public function test_user_cannot_toggle_visibility_of_system_template(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create([
            'user_id' => null,
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('templates.toggle-visibility', $systemTemplate));

        $response->assertForbidden();
    }

    public function test_toggle_visibility_requires_authentication(): void
    {
        $template = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->patch(route('templates.toggle-visibility', $template))->assertRedirect('/login');
    }

    public function test_new_templates_default_to_private(): void
    {
        $template = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertFalse($template->is_public);
    }
}
