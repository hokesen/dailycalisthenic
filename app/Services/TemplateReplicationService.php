<?php

namespace App\Services;

use App\Models\SessionTemplate;
use App\Models\User;
use App\Support\PivotDataBuilder;

class TemplateReplicationService
{
    /**
     * Ensure user owns the template, replicating if necessary
     */
    public function ensureOwnership(SessionTemplate $template, User $user): SessionTemplate
    {
        if ($this->userOwnsTemplate($template, $user)) {
            return $template;
        }

        return $this->replicateForUser($template, $user);
    }

    /**
     * Check if user owns the template
     */
    protected function userOwnsTemplate(SessionTemplate $template, User $user): bool
    {
        return $template->user_id === $user->id;
    }

    /**
     * Replicate template for user
     */
    public function replicateForUser(SessionTemplate $template, User $user): SessionTemplate
    {
        $template->loadMissing(['exercises', 'practiceBlocks']);

        $newTemplate = $template->replicate();
        $newTemplate->user_id = $user->id;
        $newTemplate->name = "{$user->name}'s {$template->name}";
        $newTemplate->save();

        $this->replicateExercises($template, $newTemplate);
        $this->replicatePracticeBlocks($template, $newTemplate);

        return $newTemplate;
    }

    /**
     * Replicate exercises with pivot data
     */
    protected function replicateExercises(SessionTemplate $source, SessionTemplate $target): void
    {
        $pivotData = [];

        foreach ($source->exercises as $exercise) {
            $pivotData[$exercise->id] = PivotDataBuilder::fromSessionTemplateExercisePivot($exercise->pivot);
        }

        if (! empty($pivotData)) {
            $target->exercises()->sync($pivotData);
        }
    }

    protected function replicatePracticeBlocks(SessionTemplate $source, SessionTemplate $target): void
    {
        foreach ($source->practiceBlocks as $block) {
            $target->practiceBlocks()->create([
                'exercise_id' => $block->exercise_id,
                'sort_order' => $block->sort_order,
                'title' => $block->title,
                'completion_mode' => $block->completion_mode,
                'duration_seconds' => $block->duration_seconds,
                'rest_after_seconds' => $block->rest_after_seconds,
                'repeats' => $block->repeats,
                'distance_label' => $block->distance_label,
                'target_cue' => $block->target_cue,
                'setup_text' => $block->setup_text,
                'notes' => $block->notes,
                'metadata' => $block->metadata,
            ]);
        }
    }
}
