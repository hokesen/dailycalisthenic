<?php

namespace App\Http\Controllers;

use App\Services\AssessmentService;
use App\Services\TrainingCatalogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentResultController extends Controller
{
    public function __construct(
        private readonly TrainingCatalogService $catalogService,
        private readonly AssessmentService $assessmentService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assessment_slug' => 'required|string',
            'recorded_on' => 'required|date',
            'results' => 'nullable|array',
            'notes' => 'nullable|string|max:10000',
        ]);

        $assessment = $this->catalogService->getAssessment($validated['assessment_slug']);
        abort_if(! $assessment, 404);

        $evaluation = $this->assessmentService->evaluate(
            $assessment,
            $validated['results'] ?? [],
        );

        $request->user()->assessmentResults()->create([
            'assessment_slug' => $validated['assessment_slug'],
            'recorded_on' => Carbon::parse($validated['recorded_on'])->toDateString(),
            'primary_result_seconds' => $evaluation['primary_result_seconds'],
            'results' => $evaluation['results'],
            'split_results' => $evaluation['split_results'],
            'derived_status' => $evaluation['derived_status'],
            'summary_label' => $evaluation['summary_label'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('home', ['tab' => 'progress'])
            ->with('success', 'Benchmark result saved.');
    }
}
