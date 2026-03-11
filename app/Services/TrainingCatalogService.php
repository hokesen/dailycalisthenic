<?php

namespace App\Services;

use App\Enums\TrainingDiscipline;
use App\Enums\TrainingProgramDayStatus;
use App\Models\Exercise;
use App\Models\PracticeBlock;
use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use Illuminate\Support\Collection;

class TrainingCatalogService
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository
    ) {}

    public function getCatalog(): array
    {
        static $catalog;

        if ($catalog !== null) {
            return $catalog;
        }

        /** @var array $catalog */
        $catalog = require database_path('data/training_catalog.php');

        return $catalog;
    }

    public function getDisciplines(): array
    {
        return $this->getCatalog()['disciplines'] ?? [];
    }

    public function getDisciplineLabel(string $discipline): string
    {
        return $this->getDisciplines()[$discipline]['label'] ?? ucfirst($discipline);
    }

    public function disciplineIsLive(string $discipline): bool
    {
        return ($this->getDisciplines()[$discipline]['status'] ?? 'planned') === 'live';
    }

    public function getPrograms(string $discipline = TrainingDiscipline::Soccer->value): Collection
    {
        return collect($this->getCatalog()[$discipline]['programs'] ?? [])
            ->map(fn (array $program, string $slug) => $this->enrichProgram($slug, $discipline, $program))
            ->values();
    }

    public function getProgram(string $programSlug, string $discipline = TrainingDiscipline::Soccer->value): ?array
    {
        $program = $this->getCatalog()[$discipline]['programs'][$programSlug] ?? null;

        if (! $program) {
            return null;
        }

        return $this->enrichProgram($programSlug, $discipline, $program);
    }

    public function getProgramDay(string $programSlug, string $programDayKey, string $discipline = TrainingDiscipline::Soccer->value): ?array
    {
        $program = $this->getProgram($programSlug, $discipline);

        if (! $program) {
            return null;
        }

        return collect($program['days'])
            ->firstWhere('key', $programDayKey);
    }

    public function getProgramDays(string $programSlug, string $discipline = TrainingDiscipline::Soccer->value): Collection
    {
        $program = $this->getProgram($programSlug, $discipline);

        if (! $program) {
            return collect();
        }

        return collect($program['days'])->values();
    }

    public function getTemplates(string $discipline = TrainingDiscipline::Soccer->value): Collection
    {
        return collect($this->getCatalog()[$discipline]['templates'] ?? [])
            ->map(function (array $template, string $slug) use ($discipline) {
                return [
                    'slug' => $slug,
                    'discipline' => $discipline,
                    ...$template,
                ];
            })
            ->values();
    }

    public function getTemplate(string $templateSlug, string $discipline = TrainingDiscipline::Soccer->value): ?array
    {
        $template = $this->getCatalog()[$discipline]['templates'][$templateSlug] ?? null;

        if (! $template) {
            return null;
        }

        return [
            'slug' => $templateSlug,
            'discipline' => $discipline,
            ...$template,
        ];
    }

    public function getAssessments(string $discipline = TrainingDiscipline::Soccer->value): Collection
    {
        return collect($this->getCatalog()[$discipline]['assessments'] ?? [])
            ->map(function (array $assessment, string $slug) use ($discipline) {
                return [
                    'slug' => $slug,
                    'discipline' => $discipline,
                    ...$assessment,
                ];
            })
            ->values();
    }

    public function getAssessment(string $assessmentSlug, string $discipline = TrainingDiscipline::Soccer->value): ?array
    {
        $assessment = $this->getCatalog()[$discipline]['assessments'][$assessmentSlug] ?? null;

        if (! $assessment) {
            return null;
        }

        return [
            'slug' => $assessmentSlug,
            'discipline' => $discipline,
            ...$assessment,
        ];
    }

    public function getDrill(string $drillSlug, string $discipline = TrainingDiscipline::Soccer->value): ?array
    {
        $drill = $this->getCatalog()[$discipline]['drills'][$drillSlug] ?? null;

        if (! $drill) {
            return null;
        }

        return [
            'slug' => $drillSlug,
            'discipline' => $discipline,
            ...$drill,
        ];
    }

    public function getDrills(string $discipline = TrainingDiscipline::Soccer->value): Collection
    {
        return collect($this->getCatalog()[$discipline]['drills'] ?? [])
            ->map(function (array $drill, string $slug) use ($discipline) {
                return [
                    'slug' => $slug,
                    'discipline' => $discipline,
                    ...$drill,
                ];
            })
            ->values();
    }

    public function materializeTemplate(string $templateSlug, string $discipline = TrainingDiscipline::Soccer->value): SessionTemplate
    {
        $definition = $this->getTemplate($templateSlug, $discipline);

        abort_if(! $definition, 404);

        $template = SessionTemplate::query()->firstOrCreate(
            [
                'user_id' => null,
                'name' => $definition['name'],
            ],
            [
                'discipline' => $discipline,
                'description' => $definition['description'] ?? null,
                'notes' => $definition['notes'] ?? null,
                'default_rest_seconds' => 30,
                'is_public' => false,
            ]
        );

        $template->update([
            'discipline' => $discipline,
            'description' => $definition['description'] ?? null,
            'notes' => $definition['notes'] ?? null,
            'is_public' => false,
        ]);

        $blocks = collect($definition['blocks'] ?? [])->values();
        $existingBlocks = $template->practiceBlocks()->get()->keyBy('sort_order');

        foreach ($blocks as $index => $blockDefinition) {
            $exercise = $this->materializeDrill($blockDefinition['drill_slug'] ?? null, $discipline);

            PracticeBlock::query()->updateOrCreate(
                [
                    'session_template_id' => $template->id,
                    'sort_order' => $index + 1,
                ],
                [
                    'exercise_id' => $exercise?->id,
                    'title' => $blockDefinition['title'],
                    'completion_mode' => $blockDefinition['completion_mode'] ?? 'timed',
                    'duration_seconds' => $blockDefinition['duration_seconds'] ?? null,
                    'rest_after_seconds' => $blockDefinition['rest_after_seconds'] ?? 0,
                    'repeats' => $blockDefinition['repeats'] ?? 1,
                    'distance_label' => $blockDefinition['distance_label'] ?? null,
                    'target_cue' => $blockDefinition['target_cue'] ?? null,
                    'setup_text' => $blockDefinition['setup_text'] ?? ($exercise?->setup_text ?? null),
                    'notes' => $blockDefinition['notes'] ?? null,
                    'metadata' => [
                        'catalog_drill_slug' => $blockDefinition['drill_slug'] ?? null,
                    ],
                ]
            );
        }

        $template->practiceBlocks()->where('sort_order', '>', $blocks->count())->delete();

        $attachableExercises = [];
        foreach ($blocks as $index => $blockDefinition) {
            $exercise = $this->materializeDrill($blockDefinition['drill_slug'] ?? null, $discipline);
            if (! $exercise) {
                continue;
            }

            $attachableExercises[$exercise->id] = [
                'order' => $index + 1,
                'duration_seconds' => $blockDefinition['duration_seconds'] ?? $exercise->default_duration_seconds,
                'rest_after_seconds' => $blockDefinition['rest_after_seconds'] ?? 0,
                'sets' => null,
                'reps' => null,
                'notes' => $blockDefinition['notes'] ?? null,
                'tempo' => null,
                'intensity' => null,
            ];
        }

        $template->exercises()->sync($attachableExercises);

        return $template->fresh(['practiceBlocks', 'exercises']);
    }

    public function materializeDrill(?string $drillSlug, string $discipline = TrainingDiscipline::Soccer->value): ?Exercise
    {
        if ($drillSlug === null) {
            return null;
        }

        $drill = $this->getDrill($drillSlug, $discipline);

        if (! $drill) {
            return null;
        }

        $exercise = Exercise::query()
            ->whereNull('user_id')
            ->where('name', $drill['name'])
            ->first();

        if ($exercise) {
            $exercise->update([
                'description' => $drill['description'] ?? null,
                'instructions' => $drill['instructions'] ?? null,
                'setup_text' => $drill['setup_text'] ?? null,
                'field_layout_notes' => $drill['field_layout_notes'] ?? null,
                'difficulty_level' => $drill['difficulty_level'] ?? null,
                'category' => $drill['category'] ?? null,
                'discipline' => $discipline,
                'default_duration_seconds' => $drill['default_duration_seconds'] ?? null,
                'media_url' => $drill['media_url'] ?? null,
            ]);

            return $exercise;
        }

        return Exercise::query()->create([
            'user_id' => null,
            'name' => $drill['name'],
            'description' => $drill['description'] ?? null,
            'instructions' => $drill['instructions'] ?? null,
            'setup_text' => $drill['setup_text'] ?? null,
            'field_layout_notes' => $drill['field_layout_notes'] ?? null,
            'difficulty_level' => $drill['difficulty_level'] ?? null,
            'category' => $drill['category'] ?? null,
            'discipline' => $discipline,
            'default_duration_seconds' => $drill['default_duration_seconds'] ?? null,
            'media_url' => $drill['media_url'] ?? null,
        ]);
    }

    protected function normalizeProgramDay(string $programSlug, array $day, string $discipline = TrainingDiscipline::Soccer->value): array
    {
        $template = isset($day['template_slug'])
            ? $this->getTemplate($day['template_slug'], $discipline)
            : null;
        $assessment = isset($day['assessment_slug'])
            ? $this->getAssessment($day['assessment_slug'], $discipline)
            : null;

        return [
            'key' => $day['key'] ?? sprintf('w%d-d%d', $day['week'], $day['day']),
            'title' => $day['title'] ?? $template['name'] ?? $assessment['name'] ?? null,
            'description' => $day['description'] ?? $template['description'] ?? $assessment['description'] ?? null,
            'template_slug' => $day['template_slug'] ?? null,
            'assessment_slug' => $day['assessment_slug'] ?? null,
            'assessment_name' => $assessment['name'] ?? null,
            'is_rest_day' => $day['is_rest_day'] ?? false,
            'notes' => $day['notes'] ?? null,
            'week' => $day['week'],
            'day' => $day['day'],
            'program_slug' => $programSlug,
        ];
    }

    protected function enrichProgram(string $programSlug, string $discipline, array $program): array
    {
        $normalizedDays = collect($program['days'] ?? [])
            ->map(fn (array $day) => $this->normalizeProgramDay($programSlug, $day, $discipline))
            ->values();

        $assessmentNames = $normalizedDays
            ->pluck('assessment_name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'slug' => $programSlug,
            'discipline' => $discipline,
            ...$program,
            'days' => $normalizedDays->all(),
            'cycle_length_days' => $normalizedDays->count(),
            'training_days_per_cycle' => $normalizedDays->where('is_rest_day', false)->count(),
            'rest_days_per_cycle' => $normalizedDays->where('is_rest_day', true)->count(),
            'assessment_count' => count($assessmentNames),
            'assessment_names' => $assessmentNames,
            'preview_days' => $normalizedDays->take(min(4, max(1, $normalizedDays->count())))->all(),
            'first_training_day' => $normalizedDays->first(fn (array $day) => ! ($day['is_rest_day'] ?? false)),
            'recommended_for' => $this->describeProgramAudience($programSlug, $program),
        ];
    }

    protected function describeProgramAudience(string $programSlug, array $program): string
    {
        return match ($program['team_practice_band'] ?? null) {
            '2_or_fewer' => 'Best when you train with the team two times per week or less.',
            '3_to_4' => 'Best when you already have three to four team sessions each week.',
            '5_plus' => 'Best when your team load is already five or more sessions each week.',
            default => str_contains($programSlug, 'pro-soccer-fitness')
                ? 'Best if you want a dedicated six-week conditioning block outside team schedule planning.'
                : 'Choose this when the structure matches your current training rhythm.',
        };
    }
}
