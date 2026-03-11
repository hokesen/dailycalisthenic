<?php

namespace App\Services;

use App\Enums\LiftCategory;
use App\Models\SessionExercise;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LiftingDashboardService
{
    /**
     * @return array{personal_records: Collection, recent_sessions: Collection, category_history: array}
     */
    public function buildDashboardData(User $user, Carbon $userNow): array
    {
        return [
            'personal_records' => $this->getPersonalRecords($user),
            'recent_sessions' => $this->getRecentSessions($user, $userNow),
            'category_history' => $this->getCategoryHistory($user, $userNow),
        ];
    }

    /**
     * Get the personal record (heaviest weight) for each lift category.
     */
    public function getPersonalRecords(User $user): Collection
    {
        return collect(LiftCategory::cases())->map(function (LiftCategory $category) use ($user) {
            $record = SessionExercise::query()
                ->with('session')
                ->whereHas('session', fn ($query) => $query->where('user_id', $user->id))
                ->where('lift_category', $category->value)
                ->whereNotNull('weight_lbs')
                ->orderByDesc('weight_lbs')
                ->orderByDesc('completed_at')
                ->orderByDesc('created_at')
                ->first();

            return [
                'category' => $category,
                'label' => $category->label(),
                'movement_pattern' => $category->movementPattern(),
                'weight_lbs' => $record?->weight_lbs,
                'reps' => $record?->reps_completed,
                'date' => $this->resolveRecordDate($record),
            ];
        });
    }

    /**
     * Get recent lifting sessions for the last 14 days.
     */
    public function getRecentSessions(User $user, Carbon $userNow): Collection
    {
        $startDate = $userNow->copy()->subDays(13)->startOfDay()->utc();

        return SessionExercise::query()
            ->whereHas('session', fn ($query) => $query->where('user_id', $user->id))
            ->whereNotNull('lift_category')
            ->where(function ($query) use ($startDate) {
                $query
                    ->where('completed_at', '>=', $startDate)
                    ->orWhere('created_at', '>=', $startDate);
            })
            ->with('session', 'exercise')
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Build history data for each lift category (last N sessions for that lift).
     *
     * @return array<string, array{sessions: Collection, trend: string}>
     */
    public function getCategoryHistory(User $user, Carbon $userNow): array
    {
        $history = [];

        foreach (LiftCategory::cases() as $category) {
            $sessions = SessionExercise::query()
                ->whereHas('session', fn ($query) => $query->where('user_id', $user->id))
                ->where('lift_category', $category->value)
                ->whereNotNull('weight_lbs')
                ->with('session')
                ->orderByDesc('completed_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $history[$category->value] = [
                'sessions' => $sessions,
                'trend' => $this->calculateTrend($sessions),
            ];
        }

        return $history;
    }

    private function calculateTrend(Collection $sessions): string
    {
        if ($sessions->count() < 2) {
            return 'neutral';
        }

        $latest = (float) $sessions->first()->weight_lbs;
        $previous = (float) $sessions->skip(1)->first()->weight_lbs;

        if ($latest > $previous) {
            return 'up';
        }

        if ($latest < $previous) {
            return 'down';
        }

        return 'neutral';
    }

    private function resolveRecordDate(?SessionExercise $record): ?Carbon
    {
        if (! $record) {
            return null;
        }

        return $record->completed_at
            ?? $record->session?->completed_at
            ?? $record->session?->started_at
            ?? $record->created_at;
    }
}
