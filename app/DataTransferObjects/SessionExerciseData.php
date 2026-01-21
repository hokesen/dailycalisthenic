<?php

namespace App\DataTransferObjects;

use App\Models\Exercise;
use App\Models\SessionTemplate;

class SessionExerciseData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $instructions,
        public ?int $sets,
        public ?int $reps,
        public int $duration_seconds,
        public int $rest_after_seconds,
        public int $order,
    ) {}

    public static function fromTemplateExercise(Exercise $exercise, SessionTemplate $template): self
    {
        return new self(
            id: $exercise->id,
            name: $exercise->name,
            description: $exercise->description,
            instructions: $exercise->instructions,
            sets: $exercise->pivot->sets,
            reps: $exercise->pivot->reps,
            duration_seconds: $exercise->pivot->duration_seconds ?? 0,
            rest_after_seconds: $exercise->pivot->rest_after_seconds ?? ($template->default_rest_seconds ?? 30),
            order: $exercise->pivot->order,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'duration_seconds' => $this->duration_seconds,
            'rest_after_seconds' => $this->rest_after_seconds,
            'order' => $this->order,
        ];
    }
}
