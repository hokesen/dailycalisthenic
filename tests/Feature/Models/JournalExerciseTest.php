<?php

namespace Tests\Feature\Models;

use App\Models\JournalEntry;
use App\Models\JournalExercise;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_exercise_belongs_to_journal_entry(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
        ]);

        $exercise = JournalExercise::create([
            'journal_entry_id' => $entry->id,
            'name' => '1 hour yoga class',
            'duration_minutes' => 60,
        ]);

        $this->assertInstanceOf(JournalEntry::class, $exercise->journalEntry);
        $this->assertEquals($entry->id, $exercise->journalEntry->id);
    }

    public function test_duration_minutes_is_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
        ]);

        $exercise = JournalExercise::create([
            'journal_entry_id' => $entry->id,
            'name' => 'Running',
            'duration_minutes' => '45',
        ]);

        $this->assertIsInt($exercise->duration_minutes);
        $this->assertEquals(45, $exercise->duration_minutes);
    }

    public function test_order_is_cast_to_integer_and_defaults_to_zero(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
        ]);

        $exercise = JournalExercise::create([
            'journal_entry_id' => $entry->id,
            'name' => 'Swimming',
        ]);

        $exercise->refresh();

        $this->assertIsInt($exercise->order);
        $this->assertEquals(0, $exercise->order);
    }

    public function test_can_create_journal_exercise_with_notes(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
        ]);

        $exercise = JournalExercise::create([
            'journal_entry_id' => $entry->id,
            'name' => 'Meditation',
            'notes' => 'Morning meditation session',
        ]);

        $this->assertEquals('Morning meditation session', $exercise->notes);
    }
}
