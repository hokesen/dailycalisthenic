<?php

namespace App\Services;

use App\Enums\TrainingDiscipline;
use App\Enums\TrainingProgramDayStatus;
use App\Models\Session;
use App\Models\TrainingProgramDayLog;
use App\Models\TrainingProgramEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class TrainingProgramService
{
    public function __construct(
        private readonly TrainingCatalogService $catalogService
    ) {}

    public function enroll(User $user, string $programSlug, CarbonInterface $startsOn, ?string $teamPracticeBand = null): TrainingProgramEnrollment
    {
        $program = $this->catalogService->getProgram($programSlug, TrainingDiscipline::Soccer->value);

        if (! $program) {
            throw ValidationException::withMessages([
                'program_slug' => 'Unknown training program.',
            ]);
        }

        $user->trainingProgramEnrollments()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return $user->trainingProgramEnrollments()->create([
            'program_slug' => $programSlug,
            'starts_on' => $startsOn->toDateString(),
            'team_practice_band' => $teamPracticeBand ?? ($program['team_practice_band'] ?? null),
            'is_active' => true,
            'metadata' => [
                'discipline' => TrainingDiscipline::Soccer->value,
            ],
        ]);
    }

    public function getActiveEnrollment(User $user, string $discipline = TrainingDiscipline::Soccer->value): ?TrainingProgramEnrollment
    {
        $enrollment = $user->activeTrainingProgramEnrollment()->with('dayLogs')->first();

        if (! $enrollment) {
            return null;
        }

        $program = $this->catalogService->getProgram($enrollment->program_slug, $discipline);

        return $program ? $enrollment : null;
    }

    public function buildDashboardState(User $user, CarbonInterface $referenceDate, string $discipline = TrainingDiscipline::Soccer->value): array
    {
        $enrollment = $this->getActiveEnrollment($user, $discipline);
        $programs = $this->catalogService->getPrograms($discipline)->all();

        if (! $enrollment) {
            return [
                'active_enrollment' => null,
                'active_program' => null,
                'today_day' => null,
                'upcoming_days' => [],
                'weekly_summary' => null,
                'programs' => $programs,
            ];
        }

        $program = $this->catalogService->getProgram($enrollment->program_slug, $discipline);
        $todayDay = $this->resolveDayForDate($enrollment, $referenceDate);
        $upcomingDays = collect(range(0, 4))
            ->map(fn (int $offset) => $this->resolveDayForDate($enrollment, $referenceDate->copy()->addDays($offset)))
            ->filter()
            ->values()
            ->all();

        return [
            'active_enrollment' => $enrollment,
            'active_program' => $program,
            'today_day' => $todayDay,
            'upcoming_days' => $upcomingDays,
            'weekly_summary' => $this->buildWeeklySummary($enrollment, $referenceDate),
            'programs' => $programs,
        ];
    }

    public function resolveDayForDate(TrainingProgramEnrollment $enrollment, CarbonInterface $date): ?array
    {
        $dateString = $date->toDateString();
        $loggedDay = $enrollment->dayLogs
            ->first(fn (TrainingProgramDayLog $log) => $log->scheduled_for?->toDateString() === $dateString);

        if ($loggedDay instanceof TrainingProgramDayLog) {
            $definition = $this->catalogService->getProgramDay($enrollment->program_slug, $loggedDay->program_day_key, TrainingDiscipline::Soccer->value);

            if (! $definition) {
                return null;
            }

            return [
                ...$definition,
                'scheduled_for' => $dateString,
                'status' => $loggedDay->status?->value ?? TrainingProgramDayStatus::Pending->value,
                'session_id' => $loggedDay->session_id,
                'log_id' => $loggedDay->id,
            ];
        }

        $program = $this->catalogService->getProgram($enrollment->program_slug, TrainingDiscipline::Soccer->value);
        if (! $program) {
            return null;
        }

        $startsOn = Carbon::parse($enrollment->starts_on)->startOfDay();
        $targetDate = Carbon::parse($dateString)->startOfDay();

        if ($targetDate->lt($startsOn)) {
            return null;
        }

        $daysSinceStart = $startsOn->diffInDays($targetDate);
        $programDays = $this->catalogService->getProgramDays($enrollment->program_slug, TrainingDiscipline::Soccer->value)->values();

        if ($programDays->isEmpty()) {
            return null;
        }

        $totalProgramDays = $programDays->count();
        $durationWeeks = (int) ($program['duration_weeks'] ?? 1);

        if ($durationWeeks > 1 && $daysSinceStart >= $totalProgramDays) {
            return null;
        }

        $dayIndex = $durationWeeks === 1
            ? $daysSinceStart % $totalProgramDays
            : $daysSinceStart;

        $definition = $programDays->get($dayIndex);

        if (! $definition) {
            return null;
        }

        return [
            ...$definition,
            'scheduled_for' => $dateString,
            'status' => TrainingProgramDayStatus::Pending->value,
            'session_id' => null,
            'log_id' => null,
        ];
    }

    public function attachDayLogToSession(
        TrainingProgramEnrollment $enrollment,
        string $programDayKey,
        CarbonInterface $scheduledFor,
        Session $session
    ): TrainingProgramDayLog {
        $log = TrainingProgramDayLog::query()->updateOrCreate(
            [
                'training_program_enrollment_id' => $enrollment->id,
                'program_day_key' => $programDayKey,
                'scheduled_for' => $scheduledFor->toDateString(),
            ],
            [
                'status' => TrainingProgramDayStatus::Pending,
                'session_id' => $session->id,
            ]
        );

        if (! $enrollment->relationLoaded('dayLogs')) {
            return $log;
        }

        $enrollment->setRelation(
            'dayLogs',
            $enrollment->dayLogs
                ->reject(fn (TrainingProgramDayLog $existingLog) => $existingLog->id === $log->id)
                ->push($log)
        );

        return $log;
    }

    public function markSessionCompleted(Session $session): void
    {
        if (! $session->training_program_enrollment_id || ! $session->program_day_key) {
            return;
        }

        $existingLog = TrainingProgramDayLog::query()
            ->where('session_id', $session->id)
            ->first();

        $scheduledFor = $existingLog?->scheduled_for?->toDateString()
            ?? $session->created_at?->copy()->timezone($session->user?->timezone ?? 'America/Los_Angeles')->toDateString()
            ?? now()->toDateString();

        TrainingProgramDayLog::query()->updateOrCreate(
            [
                'training_program_enrollment_id' => $session->training_program_enrollment_id,
                'program_day_key' => $session->program_day_key,
                'scheduled_for' => $scheduledFor,
            ],
            [
                'status' => TrainingProgramDayStatus::Completed,
                'actual_date' => now()->timezone($session->user?->timezone ?? 'America/Los_Angeles')->toDateString(),
                'session_id' => $session->id,
            ]
        );
    }

    public function skipDay(TrainingProgramEnrollment $enrollment, CarbonInterface $date): TrainingProgramDayLog
    {
        $day = $this->resolveDayForDate($enrollment, $date);

        if (! $day) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'No scheduled day found to skip.',
            ]);
        }

        return TrainingProgramDayLog::query()->updateOrCreate(
            [
                'training_program_enrollment_id' => $enrollment->id,
                'program_day_key' => $day['key'],
                'scheduled_for' => $date->toDateString(),
            ],
            [
                'status' => TrainingProgramDayStatus::Skipped,
                'actual_date' => $date->toDateString(),
            ]
        );
    }

    public function moveDay(TrainingProgramEnrollment $enrollment, CarbonInterface $fromDate, CarbonInterface $toDate): void
    {
        $day = $this->resolveDayForDate($enrollment, $fromDate);

        if (! $day) {
            throw ValidationException::withMessages([
                'scheduled_for' => 'No scheduled day found to move.',
            ]);
        }

        TrainingProgramDayLog::query()->updateOrCreate(
            [
                'training_program_enrollment_id' => $enrollment->id,
                'program_day_key' => $day['key'],
                'scheduled_for' => $fromDate->toDateString(),
            ],
            [
                'status' => TrainingProgramDayStatus::Moved,
                'actual_date' => $toDate->toDateString(),
                'notes' => 'Moved to '.$toDate->toDateString(),
            ]
        );

        TrainingProgramDayLog::query()->updateOrCreate(
            [
                'training_program_enrollment_id' => $enrollment->id,
                'program_day_key' => $day['key'],
                'scheduled_for' => $toDate->toDateString(),
            ],
            [
                'status' => TrainingProgramDayStatus::Pending,
                'notes' => 'Moved from '.$fromDate->toDateString(),
            ]
        );
    }

    protected function buildWeeklySummary(TrainingProgramEnrollment $enrollment, CarbonInterface $referenceDate): array
    {
        $program = $this->catalogService->getProgram($enrollment->program_slug, TrainingDiscipline::Soccer->value);
        $startsOn = Carbon::parse($enrollment->starts_on)->startOfDay();
        $targetDate = Carbon::parse($referenceDate->toDateString())->startOfDay();
        $daysSinceStart = max(0, $startsOn->diffInDays($targetDate));
        $weekIndex = intdiv($daysSinceStart, 7);
        $weekStart = $startsOn->copy()->addDays($weekIndex * 7);
        $weekEnd = $weekStart->copy()->addDays(6);
        $durationWeeks = max(1, (int) ($program['duration_weeks'] ?? 1));
        $programWeek = $durationWeeks === 1 ? 1 : min($durationWeeks, $weekIndex + 1);

        $programDays = $this->catalogService
            ->getProgramDays($enrollment->program_slug, TrainingDiscipline::Soccer->value)
            ->filter(fn (array $day) => (int) $day['week'] === $programWeek);

        $plannedCount = $programDays->where('is_rest_day', false)->count();
        $logs = $enrollment->dayLogs
            ->filter(fn (TrainingProgramDayLog $log) => $log->scheduled_for && $log->scheduled_for->betweenIncluded($weekStart, $weekEnd));

        $completedCount = $logs->where('status', TrainingProgramDayStatus::Completed)->count();
        $skippedCount = $logs->where('status', TrainingProgramDayStatus::Skipped)->count();
        $movedCount = $logs->where('status', TrainingProgramDayStatus::Moved)->count();

        return [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'planned_count' => $plannedCount,
            'completed_count' => $completedCount,
            'skipped_count' => $skippedCount,
            'moved_count' => $movedCount,
            'rest_count' => $programDays->where('is_rest_day', true)->count(),
        ];
    }
}
