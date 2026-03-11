<?php

namespace App\DataTransferObjects;

use App\Models\Exercise;
use App\Models\SessionTemplate;

class SessionExerciseData
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $linked_name,
        public ?string $description,
        public ?string $instructions,
        public ?string $notes,
        public ?string $setup_text,
        public ?string $field_layout_notes,
        public ?string $media_url,
        public ?int $sets,
        public ?int $reps,
        public ?int $duration_seconds,
        public int $rest_after_seconds,
        public int $order,
        public ?string $tempo = null,
        public ?string $intensity = null,
        public int $repeats = 1,
        public string $completion_mode = 'timed',
        public ?string $distance_label = null,
        public ?string $target_cue = null,
        public ?int $tracking_order = null,
        public bool $track_completion = true,
    ) {}

    public static function fromTemplateExercise(Exercise $exercise, SessionTemplate $template): self
    {
        return new self(
            id: $exercise->id,
            name: $exercise->name,
            linked_name: null,
            description: $exercise->description,
            instructions: $exercise->instructions,
            notes: $exercise->pivot->notes,
            setup_text: $exercise->setup_text,
            field_layout_notes: $exercise->field_layout_notes,
            media_url: $exercise->media_url,
            sets: $exercise->pivot->sets,
            reps: $exercise->pivot->reps,
            duration_seconds: $exercise->pivot->duration_seconds ?? 0,
            rest_after_seconds: $exercise->pivot->rest_after_seconds ?? ($template->default_rest_seconds ?? 30),
            order: $exercise->pivot->order,
            tempo: $exercise->pivot->tempo?->label(),
            intensity: $exercise->pivot->intensity?->label(),
            repeats: 1,
            completion_mode: 'timed',
            distance_label: null,
            target_cue: null,
            tracking_order: $exercise->pivot->order,
            track_completion: true,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'linked_name' => $this->linked_name,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'notes' => $this->notes,
            'setup_text' => $this->setup_text,
            'field_layout_notes' => $this->field_layout_notes,
            'media_url' => $this->media_url,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'duration_seconds' => $this->duration_seconds,
            'rest_after_seconds' => $this->rest_after_seconds,
            'order' => $this->order,
            'tempo' => $this->tempo,
            'intensity' => $this->intensity,
            'repeats' => $this->repeats,
            'completion_mode' => $this->completion_mode,
            'distance_label' => $this->distance_label,
            'target_cue' => $this->target_cue,
            'tracking_order' => $this->tracking_order,
            'track_completion' => $this->track_completion,
        ];
    }
}
