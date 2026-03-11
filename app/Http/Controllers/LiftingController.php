<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseCategory;
use App\Enums\LiftCategory;
use App\Enums\SessionStatus;
use App\Enums\TrainingDiscipline;
use App\Http\Requests\LogLiftingSetRequest;
use App\Models\Exercise;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LiftingController extends Controller
{
    /**
     * Log a lifting set for the authenticated user.
     */
    public function logSet(LogLiftingSetRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $category = LiftCategory::from($validated['lift_category']);
        $weightLbs = (float) $validated['weight_lbs'];
        $repsCompleted = (int) $validated['reps_completed'];
        $setsCompleted = (int) ($validated['sets_completed'] ?? 1);
        $nowUtc = now('UTC');

        $response = DB::transaction(function () use (
            $user,
            $category,
            $weightLbs,
            $repsCompleted,
            $setsCompleted,
            $nowUtc
        ): array {
            $template = $this->findOrCreateLiftingTemplate($user);
            $session = $this->findOrCreateTodaySession($user, $template, $nowUtc);
            $exercise = $this->findOrCreateLiftingExercise($user, $category);
            $currentPersonalRecord = SessionExercise::query()
                ->whereHas('session', fn ($query) => $query->where('user_id', $user->id))
                ->where('lift_category', $category->value)
                ->whereNotNull('weight_lbs')
                ->max('weight_lbs');

            $isPersonalRecord = $currentPersonalRecord === null || $weightLbs > (float) $currentPersonalRecord;

            $sessionExercise = $session->sessionExercises()->create([
                'exercise_id' => $exercise->id,
                'order' => $this->nextExerciseOrder($session),
                'weight_lbs' => $weightLbs,
                'reps_completed' => $repsCompleted,
                'sets_completed' => $setsCompleted,
                'lift_category' => $category->value,
                'is_personal_record' => $isPersonalRecord,
                'started_at' => $nowUtc,
                'completed_at' => $nowUtc,
            ]);

            return [
                'success' => true,
                'is_personal_record' => $isPersonalRecord,
                'weight_lbs' => (float) $sessionExercise->weight_lbs,
                'reps_completed' => (int) $sessionExercise->reps_completed,
                'sets_completed' => (int) $sessionExercise->sets_completed,
            ];
        });

        return response()->json($response);
    }

    private function findOrCreateLiftingTemplate(User $user): SessionTemplate
    {
        return SessionTemplate::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Lifting Log',
                'discipline' => TrainingDiscipline::Lifting->value,
            ],
            [
                'default_rest_seconds' => 180,
                'is_public' => false,
            ]
        );
    }

    private function findOrCreateTodaySession(User $user, SessionTemplate $template, Carbon $nowUtc): Session
    {
        $todayStartUtc = $nowUtc->copy()->startOfDay();
        $todayEndUtc = $nowUtc->copy()->endOfDay();

        $session = Session::query()
            ->where('user_id', $user->id)
            ->where('session_template_id', $template->id)
            ->whereBetween('started_at', [$todayStartUtc, $todayEndUtc])
            ->first();

        if ($session) {
            return $session;
        }

        return Session::query()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'name' => $template->name,
            'status' => SessionStatus::InProgress,
            'started_at' => $nowUtc,
        ]);
    }

    private function findOrCreateLiftingExercise(User $user, LiftCategory $category): Exercise
    {
        return Exercise::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => $category->label(),
                'discipline' => TrainingDiscipline::Lifting->value,
            ],
            [
                'category' => $this->exerciseCategoryForLift($category)->value,
                'description' => $category->label().' practice log',
            ]
        );
    }

    private function exerciseCategoryForLift(LiftCategory $category): ExerciseCategory
    {
        return match ($category->movementPattern()) {
            ExerciseCategory::Push->value => ExerciseCategory::Push,
            ExerciseCategory::Pull->value => ExerciseCategory::Pull,
            ExerciseCategory::Legs->value => ExerciseCategory::Legs,
            ExerciseCategory::FullBody->value => ExerciseCategory::FullBody,
            default => ExerciseCategory::FullBody,
        };
    }

    private function nextExerciseOrder(Session $session): int
    {
        return ((int) $session->sessionExercises()->max('order')) + 1;
    }
}
