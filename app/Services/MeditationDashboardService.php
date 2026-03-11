<?php

namespace App\Services;

use App\Models\MeditationLog;
use App\Models\User;

class MeditationDashboardService
{
    /**
     * @return array{today_log: MeditationLog|null, recent_logs: \Illuminate\Database\Eloquent\Collection<int, MeditationLog>}
     */
    public function buildDashboardData(User $user): array
    {
        $userNow = $user->now();
        $todayStartUtc = $userNow->copy()->startOfDay()->utc();
        $todayEndUtc = $userNow->copy()->endOfDay()->utc();

        $todayLog = MeditationLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('practiced_at', [$todayStartUtc, $todayEndUtc])
            ->latest('practiced_at')
            ->first();

        $sevenDaysAgoUtc = $userNow->copy()->subDays(7)->startOfDay()->utc();

        $recentLogs = MeditationLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('practiced_at', [$sevenDaysAgoUtc, $todayEndUtc])
            ->orderByDesc('practiced_at')
            ->get();

        return [
            'today_log' => $todayLog,
            'recent_logs' => $recentLogs,
        ];
    }
}
