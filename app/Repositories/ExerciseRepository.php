<?php

namespace App\Repositories;

use App\Models\Exercise;
use App\Models\User;
use App\Services\DefaultExerciseService;
use Illuminate\Support\Collection;

class ExerciseRepository
{
    public function __construct(
        protected DefaultExerciseService $defaultExerciseService
    ) {}

    /**
     * Get all exercises available to a user (defaults + user's custom exercises).
     * Database system exercises with same name as defaults are excluded.
     */
    public function getAvailableForUser(?User $user = null): Collection
    {
        // Get default exercise names for filtering
        $defaultNames = $this->defaultExerciseService->getDefaultExerciseNames();

        // Start with default exercises converted to models
        $defaults = $this->getDefaultExercisesAsModels();

        // Get user's custom exercises (if user provided)
        $userExercises = collect();
        if ($user) {
            $userExercises = Exercise::query()
                ->where('user_id', $user->id)
                ->get();
        }

        // Get database system exercises that don't overlap with defaults
        $dbSystemExercises = Exercise::query()
            ->whereNull('user_id')
            ->whereNotIn('name', $defaultNames)
            ->get();

        // Merge: defaults first, then DB system exercises, then user exercises
        return $defaults
            ->concat($dbSystemExercises)
            ->concat($userExercises)
            ->sortBy('name')
            ->values();
    }

    /**
     * Get only system/default exercises (no user exercises).
     */
    public function getSystemExercises(): Collection
    {
        $defaultNames = $this->defaultExerciseService->getDefaultExerciseNames();

        $defaults = $this->getDefaultExercisesAsModels();

        $dbSystemExercises = Exercise::query()
            ->whereNull('user_id')
            ->whereNotIn('name', $defaultNames)
            ->get();

        return $defaults
            ->concat($dbSystemExercises)
            ->sortBy('name')
            ->values();
    }

    /**
     * Convert default exercises from JSON into Exercise model instances.
     * These are not persisted but behave like models for display purposes.
     */
    public function getDefaultExercisesAsModels(): Collection
    {
        $defaults = $this->defaultExerciseService->getDefaultExercises();

        return $defaults->map(function ($data) {
            $exercise = new Exercise;
            $exercise->id = $data->id;
            $exercise->user_id = null;
            $exercise->name = $data->name;
            $exercise->description = $data->description;
            $exercise->instructions = $data->instructions;
            $exercise->difficulty_level = $data->difficulty_level;
            $exercise->category = $data->category;
            $exercise->default_duration_seconds = $data->default_duration_seconds;
            $exercise->exists = false; // Mark as not persisted
            $exercise->is_default = true;

            return $exercise;
        })->values();
    }

    /**
     * Find an exercise by ID, checking both defaults and database.
     */
    public function find(int $id): ?Exercise
    {
        // Check if it's a default exercise (negative ID)
        if ($id < 0) {
            $defaults = $this->getDefaultExercisesAsModels();

            return $defaults->firstWhere('id', $id);
        }

        return Exercise::find($id);
    }

    /**
     * Find an exercise by name, preferring defaults.
     */
    public function findByName(string $name, ?User $user = null): ?Exercise
    {
        // Check defaults first
        $defaults = $this->getDefaultExercisesAsModels();
        $default = $defaults->firstWhere('name', $name);

        if ($default) {
            return $default;
        }

        // Check database
        $query = Exercise::query()->where('name', $name);

        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            });
        } else {
            $query->whereNull('user_id');
        }

        return $query->first();
    }

    /**
     * Ensure an exercise exists in the database.
     * If it's a default exercise, create it from the JSON definition.
     */
    public function materialize(int $id): ?Exercise
    {
        // If it's a database exercise, return it
        if ($id > 0) {
            return Exercise::find($id);
        }

        // It's a default exercise - find it and create in DB if not exists
        $defaults = $this->getDefaultExercisesAsModels();
        $default = $defaults->firstWhere('id', $id);

        if (! $default) {
            return null;
        }

        // Check if already exists in DB by name
        $existing = Exercise::query()
            ->whereNull('user_id')
            ->where('name', $default->name)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create from default
        return Exercise::create([
            'user_id' => null,
            'name' => $default->name,
            'description' => $default->description,
            'instructions' => $default->instructions,
            'difficulty_level' => $default->difficulty_level,
            'category' => $default->category,
            'default_duration_seconds' => $default->default_duration_seconds,
        ]);
    }

    /**
     * Get easier variations for an exercise.
     */
    public function getEasierVariations(Exercise $exercise): Collection
    {
        // Check if this exercise has default progressions
        $progression = $this->defaultExerciseService->getProgressionForExercise($exercise->name);

        if ($progression && $progression['easier']) {
            $easier = $this->findByName($progression['easier']);
            if ($easier) {
                // Recursively get easier variations
                return collect([$easier])->concat($this->getEasierVariations($easier));
            }
        }

        // Fall back to database progressions
        return collect($exercise->getEasierVariations());
    }

    /**
     * Get harder variations for an exercise.
     */
    public function getHarderVariations(Exercise $exercise): Collection
    {
        // Check if this exercise has default progressions
        $progression = $this->defaultExerciseService->getProgressionForExercise($exercise->name);

        if ($progression && $progression['harder']) {
            $harder = $this->findByName($progression['harder']);
            if ($harder) {
                // Recursively get harder variations
                return collect([$harder])->concat($this->getHarderVariations($harder));
            }
        }

        // Fall back to database progressions
        return collect($exercise->getHarderVariations());
    }

    /**
     * Clear the default exercises cache.
     */
    public function clearCache(): void
    {
        $this->defaultExerciseService->clearCache();
    }
}
