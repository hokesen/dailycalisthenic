<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserGoalController extends Controller
{
    public function updateExerciseGoals(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'exercise_ids' => 'array',
            'exercise_ids.*' => 'integer',
        ]);

        $user = $request->user();

        // Validate that positive IDs exist in database (negative IDs are default exercises)
        if (! empty($validated['exercise_ids'])) {
            $positiveIds = array_filter($validated['exercise_ids'], fn ($id) => $id > 0);
            if (! empty($positiveIds)) {
                $existingCount = \App\Models\Exercise::whereIn('id', $positiveIds)->count();
                if ($existingCount !== count($positiveIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Some exercise IDs are invalid',
                    ], 422);
                }
            }
        }

        // Deduplicate exercises by name - prefer database IDs over default IDs
        $exerciseIds = $validated['exercise_ids'] ?? [];
        if (! empty($exerciseIds)) {
            $exerciseIds = $this->deduplicateExercisesByName($exerciseIds);
        }

        // Get or create active user goal
        $userGoal = $user->goals()->active()->first();

        if (! $userGoal) {
            $userGoal = $user->goals()->create([
                'sessions_per_week' => 3,
                'minimum_session_duration_minutes' => 10,
                'is_active' => true,
                'starts_at' => now(),
            ]);
        }

        // Update exercise goals
        $userGoal->update([
            'exercise_goals' => $exerciseIds,
        ]);

        // Invalidate analytics cache so the gantt chart updates with new goals
        app(\App\Services\CachedProgressionAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'success' => true,
            'exercise_goals' => $userGoal->exercise_goals,
        ]);
    }

    /**
     * Deduplicate exercises by name, preferring database IDs over default IDs.
     * If an exercise exists with both a negative ID (default) and positive ID (database),
     * only keep the database version.
     */
    private function deduplicateExercisesByName(array $exerciseIds): array
    {
        $exerciseRepository = app(\App\Repositories\ExerciseRepository::class);
        $byName = [];

        // Group exercises by name
        foreach ($exerciseIds as $id) {
            $exercise = $exerciseRepository->find($id);
            if (! $exercise) {
                // Keep IDs we can't resolve (shouldn't happen, but be safe)
                $byName['_unknown_' . $id] = [$id];
                continue;
            }

            $name = $exercise->name;
            if (! isset($byName[$name])) {
                $byName[$name] = [];
            }
            $byName[$name][] = $id;
        }

        // For each name, prefer database ID (positive) over default ID (negative)
        $deduped = [];
        foreach ($byName as $name => $ids) {
            if (count($ids) === 1) {
                // Only one ID for this name, keep it
                $deduped[] = $ids[0];
                continue;
            }

            // Multiple IDs for same name - prefer positive (database) over negative (default)
            $positiveIds = array_values(array_filter($ids, fn ($id) => $id > 0));
            $negativeIds = array_values(array_filter($ids, fn ($id) => $id < 0));

            if (! empty($positiveIds)) {
                // Use database version
                $deduped[] = $positiveIds[0];
            } elseif (! empty($negativeIds)) {
                // Use default version
                $deduped[] = $negativeIds[0];
            }
        }

        return array_values($deduped);
    }
}
