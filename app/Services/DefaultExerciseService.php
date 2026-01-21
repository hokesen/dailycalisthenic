<?php

namespace App\Services;

use App\Enums\ExerciseCategory;
use App\Enums\ExerciseDifficulty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DefaultExerciseService
{
    protected const CACHE_KEY = 'default_exercises';

    protected const CACHE_TTL = 60 * 60; // 1 hour

    protected ?array $data = null;

    public function getData(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $this->data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->loadFromFile();
        });

        return $this->data;
    }

    protected function loadFromFile(): array
    {
        $path = database_path('data/exercises.json');

        if (! file_exists($path)) {
            return ['exercises' => [], 'progressions' => []];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['exercises' => [], 'progressions' => []];
        }

        return $data;
    }

    public function getDefaultExercises(): Collection
    {
        $data = $this->getData();

        return collect($data['exercises'] ?? [])->map(function ($exercise, $index) {
            return $this->hydrateExercise($exercise, $index);
        })->keyBy('name');
    }

    public function getDefaultExerciseNames(): array
    {
        $data = $this->getData();

        return array_column($data['exercises'] ?? [], 'name');
    }

    public function getProgressions(): Collection
    {
        $data = $this->getData();

        return collect($data['progressions'] ?? []);
    }

    protected function hydrateExercise(array $data, int $index): object
    {
        $id = -1000 - $index; // Negative IDs for default exercises

        return (object) [
            'id' => $id,
            'user_id' => null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'difficulty_level' => isset($data['difficulty_level'])
                ? ExerciseDifficulty::tryFrom($data['difficulty_level'])
                : null,
            'category' => isset($data['category'])
                ? ExerciseCategory::tryFrom($data['category'])
                : null,
            'default_duration_seconds' => $data['default_duration_seconds'] ?? null,
            'is_default' => true,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    public function getProgressionForExercise(string $exerciseName): ?array
    {
        $progressions = $this->getProgressions();

        foreach ($progressions as $progression) {
            $exercises = $progression['exercises'] ?? [];
            $index = array_search($exerciseName, $exercises);

            if ($index !== false) {
                return [
                    'path_name' => $progression['path_name'],
                    'order' => $index,
                    'easier' => $index > 0 ? $exercises[$index - 1] : null,
                    'harder' => $index < count($exercises) - 1 ? $exercises[$index + 1] : null,
                ];
            }
        }

        return null;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->data = null;
    }
}
