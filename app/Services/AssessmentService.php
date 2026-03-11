<?php

namespace App\Services;

use App\Models\AssessmentResult;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function evaluate(array $assessment, array $submittedResults): array
    {
        $schema = collect($assessment['input_schema'] ?? []);
        $normalized = [];
        $splitResults = [];
        $summaryParts = [];

        foreach ($schema as $field) {
            $key = $field['key'];
            $value = $submittedResults[$key] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (($field['type'] ?? null) === 'duration') {
                $seconds = $this->parseDurationToSeconds((string) $value);
                $normalized[$key] = $seconds;
                $summaryParts[] = "{$field['label']}: ".$this->formatDuration($seconds);
                continue;
            }

            if (($field['type'] ?? null) === 'multiline') {
                $splitResults[$key] = $this->parseSplitLines((string) $value);
                continue;
            }

            $normalized[$key] = $value;
            $summaryParts[] = "{$field['label']}: {$value}";
        }

        $derivedStatus = $this->deriveStatus($assessment, $normalized);

        return [
            'primary_result_seconds' => $this->derivePrimaryResult($normalized),
            'results' => $normalized,
            'split_results' => $splitResults === [] ? null : $splitResults,
            'derived_status' => $derivedStatus,
            'summary_label' => implode(' • ', $summaryParts),
        ];
    }

    public function buildAssessmentView(array $assessment, Collection $results): array
    {
        $latest = $results->first();
        $history = $results->take(5)->map(fn (AssessmentResult $result) => [
            'id' => $result->id,
            'recorded_on' => $result->recorded_on,
            'summary_label' => $result->summary_label,
            'derived_status' => $result->derived_status,
        ])->values();

        return [
            'assessment' => $assessment,
            'latest' => $latest,
            'history' => $history,
            'comparison_rows' => $this->buildComparisonRows($assessment, $latest),
            'trend' => $this->buildTrendSummary($results),
        ];
    }

    public function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    protected function derivePrimaryResult(array $normalizedResults): ?int
    {
        $numericValues = collect($normalizedResults)
            ->filter(fn ($value) => is_int($value) || is_float($value))
            ->map(fn ($value) => (int) $value)
            ->values();

        if ($numericValues->isEmpty()) {
            return null;
        }

        return $numericValues->sum();
    }

    protected function deriveStatus(array $assessment, array $normalizedResults): string
    {
        $targetRules = $assessment['target_rules'] ?? [];
        $passFields = collect($targetRules['pass_fields'] ?? []);
        $schemaByKey = collect($assessment['input_schema'] ?? [])->keyBy('key');

        if ($passFields->isEmpty()) {
            return 'logged';
        }

        $allPassed = $passFields->every(function (string $fieldKey) use ($schemaByKey, $normalizedResults) {
            $targetSeconds = $schemaByKey[$fieldKey]['target_seconds'] ?? null;
            $actualSeconds = $normalizedResults[$fieldKey] ?? null;

            if (! is_numeric($targetSeconds) || ! is_numeric($actualSeconds)) {
                return false;
            }

            return (int) $actualSeconds <= (int) $targetSeconds;
        });

        return $allPassed ? 'pass' : 'needs_work';
    }

    protected function buildComparisonRows(array $assessment, ?AssessmentResult $result): array
    {
        if (! $result) {
            return [];
        }

        $storedResults = collect($result->results ?? []);

        return collect($assessment['input_schema'] ?? [])
            ->filter(fn (array $field) => ($field['type'] ?? null) === 'duration')
            ->map(function (array $field) use ($storedResults) {
                $actual = $storedResults->get($field['key']);
                $target = $field['target_seconds'] ?? null;

                return [
                    'label' => $field['label'],
                    'actual' => is_numeric($actual) ? $this->formatDuration((int) $actual) : null,
                    'target' => is_numeric($target) ? $this->formatDuration((int) $target) : null,
                    'met_target' => is_numeric($actual) && is_numeric($target) ? (int) $actual <= (int) $target : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function buildTrendSummary(Collection $results): ?array
    {
        $results = $results
            ->filter(fn (AssessmentResult $result) => $result->primary_result_seconds !== null)
            ->values();

        if ($results->count() < 2) {
            return null;
        }

        $latest = $results->first();
        $previous = $results->skip(1)->first();

        if (! $latest || ! $previous) {
            return null;
        }

        $delta = (int) $latest->primary_result_seconds - (int) $previous->primary_result_seconds;

        return [
            'delta_seconds' => $delta,
            'direction' => $delta <= 0 ? 'improved' : 'slower',
            'label' => ($delta <= 0 ? '-' : '+').$this->formatDuration(abs($delta)),
        ];
    }

    protected function parseDurationToSeconds(string $value): int
    {
        if (preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (preg_match('/^(?:(\d+):)?(\d{1,2})(?:\.(\d+))?$/', $value, $matches) !== 1) {
            throw ValidationException::withMessages([
                'results' => 'Use seconds or m:ss for time fields.',
            ]);
        }

        $minutes = isset($matches[1]) ? (int) $matches[1] : 0;
        $seconds = (int) $matches[2];

        return ($minutes * 60) + $seconds;
    }

    protected function parseSplitLines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
