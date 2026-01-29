<?php

namespace App\Services;

use App\Models\SessionTemplate;

class ExerciseOrderService
{
    /**
     * Move an exercise up one position in the template.
     */
    public function moveUp(SessionTemplate $template, int $exerciseId): void
    {
        $template->load('exercises');

        $currentExercise = $template->exercises->firstWhere('id', $exerciseId);

        if (! $currentExercise || $currentExercise->pivot->order <= 1) {
            return;
        }

        $currentOrder = $currentExercise->pivot->order;
        $previousExercise = $template->exercises->firstWhere('pivot.order', $currentOrder - 1);

        if ($previousExercise) {
            $this->swapExerciseOrders($template, $currentExercise->id, $previousExercise->id, $currentOrder, $currentOrder - 1);
        }
    }

    /**
     * Move an exercise down one position in the template.
     */
    public function moveDown(SessionTemplate $template, int $exerciseId): void
    {
        $template->load('exercises');

        $currentExercise = $template->exercises->firstWhere('id', $exerciseId);

        if (! $currentExercise || $currentExercise->pivot->order >= $template->exercises->count()) {
            return;
        }

        $currentOrder = $currentExercise->pivot->order;
        $nextExercise = $template->exercises->firstWhere('pivot.order', $currentOrder + 1);

        if ($nextExercise) {
            $this->swapExerciseOrders($template, $currentExercise->id, $nextExercise->id, $currentOrder, $currentOrder + 1);
        }
    }

    /**
     * Reorder all exercises sequentially starting from 1.
     */
    public function reorder(SessionTemplate $template): void
    {
        $exercises = $template->exercises()->orderByPivot('order')->get();

        foreach ($exercises as $index => $exercise) {
            $template->exercises()->updateExistingPivot($exercise->id, [
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Swap two exercises using a temporary order to avoid unique constraint violations.
     */
    protected function swapExerciseOrders(SessionTemplate $template, int $exerciseId1, int $exerciseId2, int $order1, int $order2): void
    {
        $tempOrder = 9999;

        // Move first exercise to temp
        $template->exercises()->updateExistingPivot($exerciseId1, ['order' => $tempOrder]);

        // Move second exercise to first position
        $template->exercises()->updateExistingPivot($exerciseId2, ['order' => $order1]);

        // Move first exercise to second position
        $template->exercises()->updateExistingPivot($exerciseId1, ['order' => $order2]);
    }
}
