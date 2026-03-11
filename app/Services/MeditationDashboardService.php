<?php

namespace App\Services;

use App\Models\MeditationLog;
use App\Models\User;
use Carbon\Carbon;

class MeditationDashboardService
{
    /**
     * @return array{today_log: ?MeditationLog, total_sessions: int, total_minutes: int, current_streak: int}
     */
    public function buildDashboardData(User $user, Carbon $userNow): array
    {
        $todayStart = $userNow->copy()->startOfDay()->utc();
        $todayEnd = $userNow->copy()->endOfDay()->utc();

        $todayLog = MeditationLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('practiced_at', [$todayStart, $todayEnd])
            ->latest('practiced_at')
            ->first();

        $totalSessions = MeditationLog::query()
            ->where('user_id', $user->id)
            ->count();

        $totalSeconds = (int) MeditationLog::query()
            ->where('user_id', $user->id)
            ->sum('duration_seconds');

        return [
            'today_log' => $todayLog,
            'total_sessions' => $totalSessions,
            'total_minutes' => (int) round($totalSeconds / 60),
            'current_streak' => $this->calculateStreak($user, $userNow),
        ];
    }

    private function calculateStreak(User $user, Carbon $userNow): int
    {
        $streak = 0;
        $checkDate = $userNow->copy()->startOfDay();

        while (true) {
            $dayStart = $checkDate->copy()->startOfDay()->utc();
            $dayEnd = $checkDate->copy()->endOfDay()->utc();

            $hasPractice = MeditationLog::query()
                ->where('user_id', $user->id)
                ->whereBetween('practiced_at', [$dayStart, $dayEnd])
                ->exists();

            if (! $hasPractice) {
                break;
            }

            $streak++;
            $checkDate->subDay();
        }

        return $streak;
    }
}
