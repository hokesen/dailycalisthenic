<?php

namespace App\Services;

use App\DataTransferObjects\SessionExerciseData;
use App\Enums\PracticeBlockCompletionMode;
use App\Models\PracticeBlock;
use App\Models\Session;
use App\Models\SessionTemplate;
use Illuminate\Support\Collection;

class PracticeSessionService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildPracticeItems(SessionTemplate $template): Collection
    {
        if ($template->relationLoaded('practiceBlocks') && $template->practiceBlocks->isNotEmpty()) {
            return $template->practiceBlocks
                ->map(fn (PracticeBlock $block) => $this->buildPracticeItemFromBlock($block)->toArray())
                ->values();
        }

        return $template->exercises
            ->map(fn ($exercise) => SessionExerciseData::fromTemplateExercise($exercise, $template)->toArray())
            ->values();
    }

    public function createSessionExercises(Session $session, SessionTemplate $template): void
    {
        if ($template->relationLoaded('practiceBlocks') && $template->practiceBlocks->isNotEmpty()) {
            foreach ($template->practiceBlocks as $block) {
                if (! $block->exercise_id) {
                    continue;
                }

                $plannedDuration = $block->duration_seconds;
                if ($plannedDuration !== null) {
                    $plannedDuration *= max(1, (int) $block->repeats);
                }

                $session->sessionExercises()->create([
                    'exercise_id' => $block->exercise_id,
                    'order' => $block->sort_order,
                    'duration_seconds' => $plannedDuration ?? 0,
                    'notes' => $block->notes,
                    'tempo' => null,
                    'intensity' => null,
                ]);
            }

            return;
        }

        foreach ($template->exercises as $exercise) {
            $session->sessionExercises()->create([
                'exercise_id' => $exercise->id,
                'order' => $exercise->pivot->order,
                'duration_seconds' => $exercise->pivot->duration_seconds ?? 0,
                'tempo' => $exercise->pivot->tempo?->value,
                'intensity' => $exercise->pivot->intensity?->value,
                'notes' => $exercise->pivot->notes,
            ]);
        }
    }

    public function buildPracticeItemFromBlock(PracticeBlock $block): SessionExerciseData
    {
        $exercise = $block->exercise;
        $distanceLabel = $this->formatDistanceLabel($block->distance_label);

        return new SessionExerciseData(
            id: $block->exercise_id,
            name: $block->title,
            linked_name: $this->formatLinkedName($exercise?->name, $distanceLabel),
            description: $exercise?->description,
            instructions: $exercise?->instructions,
            notes: $block->notes,
            setup_text: $block->setup_text ?? $exercise?->setup_text,
            field_layout_notes: $exercise?->field_layout_notes,
            media_url: $exercise?->media_url,
            sets: null,
            reps: null,
            duration_seconds: $block->duration_seconds,
            rest_after_seconds: $block->rest_after_seconds,
            order: $block->sort_order,
            tempo: null,
            intensity: null,
            repeats: max(1, (int) $block->repeats),
            completion_mode: ($block->completion_mode ?? PracticeBlockCompletionMode::Timed)->value,
            distance_label: $distanceLabel,
            target_cue: $block->target_cue,
            tracking_order: $block->sort_order,
            track_completion: $block->exercise_id !== null,
        );
    }

    private function formatDistanceLabel(?string $distanceLabel): ?string
    {
        if ($distanceLabel === null) {
            return null;
        }

        return preg_replace_callback('/(\d+(?:\.\d+)?)\s*yds?\b/i', function (array $matches): string {
            $value = $matches[1];
            $unit = (float) $value === 1.0 ? 'yard' : 'yards';

            return "{$value} {$unit}";
        }, $distanceLabel);
    }

    private function formatLinkedName(?string $linkedName, ?string $distanceLabel): ?string
    {
        if ($linkedName === null || $distanceLabel === null) {
            return $linkedName;
        }

        if (preg_match('/\b(?:yds?|yards?)\b/i', $linkedName)) {
            return $linkedName;
        }

        if (! preg_match('/^(\d+(?:\.\d+)?)\s+yards?$/i', $distanceLabel, $matches)) {
            return $linkedName;
        }

        $distance = $matches[1];

        if (! preg_match('/\b'.preg_quote($distance, '/').'$/', $linkedName)) {
            return $linkedName;
        }

        return preg_replace('/\b'.preg_quote($distance, '/').'$/', "{$distance} yards", $linkedName) ?: $linkedName;
    }
}
