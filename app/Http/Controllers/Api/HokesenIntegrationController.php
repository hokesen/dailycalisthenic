<?php

namespace App\Http\Controllers\Api;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHokesenJournalLineRequest;
use App\Models\JournalEntry;
use App\Models\Session;
use App\Models\User;
use App\Services\CachedStreakService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class HokesenIntegrationController extends Controller
{
    public function __construct(
        private readonly CachedStreakService $streakService
    ) {}

    public function quickStats(Request $request): JsonResponse
    {
        $user = $this->integrationUser($request);
        $practicedSeconds = $this->practicedSecondsLastSevenDays($user);

        return response()->json([
            'email' => strtolower($user->email),
            'streak_days' => $this->streakService->calculateStreak($user),
            'practiced_seconds_last_7_days' => $practicedSeconds,
            'practiced_minutes_last_7_days' => (int) floor($practicedSeconds / 60),
            'timezone' => $user->timezone ?? 'America/Los_Angeles',
            'as_of' => now()->toIso8601String(),
        ]);
    }

    public function storeJournalLine(StoreHokesenJournalLineRequest $request): JsonResponse
    {
        $user = $this->integrationUser($request);
        $payload = $request->validated();
        $entryDate = $this->resolveEntryDate(
            $user,
            $payload['entry_date'] ?? null
        );

        $idempotencyKey = $this->resolveIdempotencyKey($request, $payload['idempotency_key'] ?? null);
        if ($idempotencyKey !== null) {
            $cached = Cache::get($this->idempotencyCacheKey($user->id, $entryDate, $idempotencyKey));
            if (is_array($cached)) {
                return response()->json($cached + ['idempotent_replay' => true]);
            }
        }

        $text = $payload['text'];
        $entry = JournalEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $entryDate)
            ->first();

        $created = false;
        $appended = false;
        $changed = false;

        if (! $entry) {
            $entry = JournalEntry::query()->create([
                'user_id' => $user->id,
                'entry_date' => $entryDate,
                'notes' => $text,
            ]);

            $created = true;
            $changed = true;
        } else {
            [$updatedNotes, $changed, $appended] = $this->mergeJournalLine(
                $entry->notes,
                $text
            );

            if ($changed) {
                $entry->update(['notes' => $updatedNotes]);
            }
        }

        if ($changed) {
            $this->streakService->invalidateUserCache($user->id);
        }

        $responsePayload = [
            'entry_id' => $entry->id,
            'entry_date' => $entry->entry_date->toDateString(),
            'created' => $created,
            'appended' => $appended,
        ];

        if ($idempotencyKey !== null) {
            Cache::put(
                $this->idempotencyCacheKey($user->id, $entryDate, $idempotencyKey),
                $responsePayload,
                now()->addDay()
            );
        }

        return response()->json($responsePayload, $created ? 201 : 200);
    }

    private function integrationUser(Request $request): User
    {
        $user = $request->attributes->get('hokesen.user');

        if (! $user instanceof User) {
            abort(500, 'Integration user is missing from request context.');
        }

        return $user;
    }

    /**
     * @return array{0: string, 1: bool, 2: bool}
     */
    private function mergeJournalLine(?string $existingNotes, string $newLine): array
    {
        $existing = trim((string) $existingNotes);
        if ($existing === '') {
            return [$newLine, true, false];
        }

        $lines = preg_split('/\r\n|\r|\n/', $existing) ?: [];
        if (in_array($newLine, $lines, true)) {
            return [$existing, false, false];
        }

        return [$existing.PHP_EOL.$newLine, true, true];
    }

    private function resolveEntryDate(User $user, ?string $entryDate): string
    {
        if ($entryDate === null) {
            return $user->now()->toDateString();
        }

        $timezone = $user->timezone ?? 'America/Los_Angeles';
        $requestedDate = Carbon::createFromFormat('Y-m-d', $entryDate, $timezone)->startOfDay();
        $userNow = $user->now();

        if ($requestedDate->greaterThan($userNow->copy()->endOfDay())) {
            throw ValidationException::withMessages([
                'entry_date' => 'entry_date cannot be in the future.',
            ]);
        }

        return $requestedDate->toDateString();
    }

    private function resolveIdempotencyKey(Request $request, ?string $idempotencyKey): ?string
    {
        if (is_string($request->header('Idempotency-Key')) && trim($request->header('Idempotency-Key')) !== '') {
            return trim($request->header('Idempotency-Key'));
        }

        if (is_string($idempotencyKey) && $idempotencyKey !== '') {
            return $idempotencyKey;
        }

        return null;
    }

    private function idempotencyCacheKey(int $userId, string $entryDate, string $idempotencyKey): string
    {
        return 'integrations:hokesen:journal-line:'.$userId.':'.$entryDate.':'.sha1($idempotencyKey);
    }

    private function practicedSecondsLastSevenDays(User $user): int
    {
        $userNow = $user->now();
        $startUtc = $userNow->copy()->subDays(6)->startOfDay()->timezone('UTC');
        $endUtc = $userNow->copy()->endOfDay()->timezone('UTC');

        return (int) Session::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [SessionStatus::Completed->value, SessionStatus::InProgress->value])
            ->where('total_duration_seconds', '>', 0)
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->whereBetween('completed_at', [$startUtc, $endUtc])
                    ->orWhereBetween('started_at', [$startUtc, $endUtc]);
            })
            ->sum('total_duration_seconds');
    }
}
