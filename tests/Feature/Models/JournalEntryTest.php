<?php

namespace Tests\Feature\Models;

use App\Models\JournalEntry;
use App\Models\JournalExercise;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_entry_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
            'notes' => 'Test notes',
        ]);

        $this->assertInstanceOf(User::class, $entry->user);
        $this->assertEquals($user->id, $entry->user->id);
    }

    public function test_journal_entry_has_many_journal_exercises(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::today(),
        ]);

        JournalExercise::create([
            'journal_entry_id' => $entry->id,
            'name' => '1 hour yoga class',
            'duration_minutes' => 60,
        ]);

        $this->assertCount(1, $entry->journalExercises);
        $this->assertEquals('1 hour yoga class', $entry->journalExercises->first()->name);
    }

    public function test_scope_for_date_filters_by_date(): void
    {
        $user = User::factory()->create();
        $today = Carbon::parse('2026-01-20');
        $yesterday = Carbon::parse('2026-01-19');

        $todayEntry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => $today,
        ]);

        $yesterdayEntry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => $yesterday,
        ]);

        $results = JournalEntry::forDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($todayEntry->id, $results->first()->id);
    }

    public function test_scope_in_date_range_filters_correctly(): void
    {
        $user = User::factory()->create();
        $startDate = Carbon::parse('2026-01-15');
        $endDate = Carbon::parse('2026-01-20');

        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::parse('2026-01-14'),
        ]);

        $entry1 = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::parse('2026-01-16'),
        ]);

        $entry2 = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::parse('2026-01-19'),
        ]);

        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => Carbon::parse('2026-01-21'),
        ]);

        $results = JournalEntry::inDateRange($startDate, $endDate)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $entry1->id));
        $this->assertTrue($results->contains('id', $entry2->id));
    }

    public function test_entry_date_is_cast_to_date(): void
    {
        $user = User::factory()->create();
        $entry = JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => '2026-01-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $entry->entry_date);
    }

    public function test_unique_constraint_on_user_and_date(): void
    {
        $user = User::factory()->create();
        $date = Carbon::today();

        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => $date,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        JournalEntry::create([
            'user_id' => $user->id,
            'entry_date' => $date,
        ]);
    }
}
