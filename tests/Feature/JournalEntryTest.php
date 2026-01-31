<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\JournalExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_journal_entry(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('journal.store'), [
                'notes' => 'Great practice session today!',
            ]);

        $response->assertRedirect(route('home'));
        $entry = JournalEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertEquals(now()->toDateString(), $entry->entry_date->toDateString());
        $this->assertEquals('Great practice session today!', $entry->notes);
    }

    public function test_user_can_update_journal_notes(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::factory()->create([
            'user_id' => $user->id,
            'notes' => 'Original notes',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('journal.update', $entry), [
                'notes' => 'Updated notes',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_can_add_exercise_to_journal(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('journal.exercises.store', $entry), [
                'name' => 'Push-ups',
                'duration_minutes' => 10,
                'notes' => 'Felt good',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('journal_exercises', [
            'journal_entry_id' => $entry->id,
            'name' => 'Push-ups',
            'duration_minutes' => 10,
            'notes' => 'Felt good',
        ]);
    }

    public function test_user_can_delete_journal_entry(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('journal.destroy', $entry));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_users_can_only_see_own_journals(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherEntry = JournalEntry::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($user)
            ->patch(route('journal.update', $otherEntry), [
                'notes' => 'Trying to update',
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_journal_exercise(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::factory()->create(['user_id' => $user->id]);
        $exercise = JournalExercise::factory()->create(['journal_entry_id' => $entry->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('journal.exercises.destroy', $exercise));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseMissing('journal_exercises', [
            'id' => $exercise->id,
        ]);
    }

    public function test_journal_exercises_have_correct_order(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('journal.exercises.store', $entry), [
                'name' => 'First Exercise',
                'duration_minutes' => 5,
            ]);

        $this->actingAs($user)
            ->post(route('journal.exercises.store', $entry), [
                'name' => 'Second Exercise',
                'duration_minutes' => 10,
            ]);

        $exercises = $entry->fresh()->journalExercises;
        $this->assertEquals('First Exercise', $exercises[0]->name);
        $this->assertEquals(1, $exercises[0]->order);
        $this->assertEquals('Second Exercise', $exercises[1]->name);
        $this->assertEquals(2, $exercises[1]->order);
    }

    public function test_creating_journal_entry_for_today_updates_existing_entry(): void
    {
        $user = User::factory()->create();
        $existingEntry = JournalEntry::factory()->create([
            'user_id' => $user->id,
            'entry_date' => now()->toDateString(),
            'notes' => 'Original notes',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('journal.store'), [
                'notes' => 'Updated notes',
            ]);

        $response->assertRedirect(route('home'));

        $this->assertEquals(1, JournalEntry::where('user_id', $user->id)
            ->whereDate('entry_date', now()->toDateString())
            ->count());

        $this->assertDatabaseHas('journal_entries', [
            'id' => $existingEntry->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_can_add_exercise_to_new_journal_entry(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('journal.exercises.store', 'new'), [
                'name' => 'Running',
                'duration_minutes' => 30,
            ]);

        $response->assertRedirect(route('home'));

        $entry = JournalEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals(now()->toDateString(), $entry->entry_date->toDateString());

        $this->assertDatabaseHas('journal_exercises', [
            'name' => 'Running',
            'duration_minutes' => 30,
        ]);
    }
}
