<?php

namespace Tests\Feature\Services;

use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Services\ExerciseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExerciseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExerciseOrderService;
    }

    public function test_move_up_swaps_with_previous_exercise(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->create(['name' => 'Exercise 3']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);
        $template->exercises()->attach($exercise3->id, ['order' => 3]);

        $template->refresh();

        $this->service->moveUp($template, $exercise2->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(2, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(1, $template->exercises->find($exercise2->id)->pivot->order);
        $this->assertEquals(3, $template->exercises->find($exercise3->id)->pivot->order);
    }

    public function test_move_up_does_nothing_when_already_at_top(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);

        $template->refresh();

        $this->service->moveUp($template, $exercise1->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise2->id)->pivot->order);
    }

    public function test_move_up_does_nothing_when_exercise_not_found(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);
        $nonExistentExercise = Exercise::factory()->create(['name' => 'Not in template']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);

        $template->refresh();

        $this->service->moveUp($template, $nonExistentExercise->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise2->id)->pivot->order);
    }

    public function test_move_down_swaps_with_next_exercise(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->create(['name' => 'Exercise 3']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);
        $template->exercises()->attach($exercise3->id, ['order' => 3]);

        $template->refresh();

        $this->service->moveDown($template, $exercise2->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(3, $template->exercises->find($exercise2->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise3->id)->pivot->order);
    }

    public function test_move_down_does_nothing_when_already_at_bottom(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);

        $template->refresh();

        $this->service->moveDown($template, $exercise2->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise2->id)->pivot->order);
    }

    public function test_move_down_does_nothing_when_exercise_not_found(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);
        $nonExistentExercise = Exercise::factory()->create(['name' => 'Not in template']);

        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 2]);

        $template->refresh();

        $this->service->moveDown($template, $nonExistentExercise->id);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise2->id)->pivot->order);
    }

    public function test_reorder_reindexes_all_exercises_sequentially(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $exercise1 = Exercise::factory()->create(['name' => 'Exercise 1']);
        $exercise2 = Exercise::factory()->create(['name' => 'Exercise 2']);
        $exercise3 = Exercise::factory()->create(['name' => 'Exercise 3']);

        // Create with gaps in order
        $template->exercises()->attach($exercise1->id, ['order' => 1]);
        $template->exercises()->attach($exercise2->id, ['order' => 5]);
        $template->exercises()->attach($exercise3->id, ['order' => 10]);

        $template->refresh();

        $this->service->reorder($template);

        $template->refresh();
        $template->load('exercises');

        $this->assertEquals(1, $template->exercises->find($exercise1->id)->pivot->order);
        $this->assertEquals(2, $template->exercises->find($exercise2->id)->pivot->order);
        $this->assertEquals(3, $template->exercises->find($exercise3->id)->pivot->order);
    }
}
