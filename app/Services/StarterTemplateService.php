<?php

namespace App\Services;

use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StarterTemplateService
{
    public function __construct(
        protected ExerciseRepository $exerciseRepository
    ) {}

    public function ensureStarterTemplates(): void
    {
        $config = config('starter-templates');

        if (! $config || empty($config['templates'])) {
            return;
        }

        $version = $config['version'] ?? 1;
        $cacheKey = "starter-templates:version:{$version}";

        $templateNames = collect($config['templates'])->pluck('name');
        $existingCount = SessionTemplate::query()
            ->whereNull('user_id')
            ->whereIn('name', $templateNames)
            ->count();

        if ($existingCount === $templateNames->count() && Cache::get($cacheKey)) {
            return;
        }

        foreach ($config['templates'] as $templateData) {
            $template = SessionTemplate::query()
                ->whereNull('user_id')
                ->where('name', $templateData['name'])
                ->first();

            if (! $template) {
                $template = SessionTemplate::create([
                    'user_id' => null,
                    'name' => $templateData['name'],
                    'description' => $templateData['description'] ?? null,
                    'default_rest_seconds' => $templateData['default_rest_seconds'] ?? null,
                    'is_public' => $templateData['is_public'] ?? true,
                ]);
            } else {
                $template->update([
                    'description' => $templateData['description'] ?? $template->description,
                    'default_rest_seconds' => $templateData['default_rest_seconds'] ?? $template->default_rest_seconds,
                    'is_public' => $templateData['is_public'] ?? $template->is_public,
                ]);
            }

            $syncData = [];

            foreach ($templateData['exercises'] as $index => $exerciseData) {
                $exercise = $this->exerciseRepository->findByName($exerciseData['name']);

                if (! $exercise) {
                    Log::warning('Starter template exercise not found', [
                        'template' => $templateData['name'],
                        'exercise' => $exerciseData['name'],
                    ]);
                    continue;
                }

                if ($exercise->id < 0) {
                    $exercise = $this->exerciseRepository->materialize($exercise->id);
                }

                if (! $exercise) {
                    Log::warning('Starter template exercise could not be materialized', [
                        'template' => $templateData['name'],
                        'exercise' => $exerciseData['name'],
                    ]);
                    continue;
                }

                $syncData[$exercise->id] = [
                    'order' => $index + 1,
                    'duration_seconds' => $exerciseData['duration'],
                    'rest_after_seconds' => $exerciseData['rest'] ?? 0,
                ];
            }

            if (! empty($syncData)) {
                $template->exercises()->sync($syncData);
            }
        }

        Cache::put($cacheKey, true, now()->addDay());
    }
}
