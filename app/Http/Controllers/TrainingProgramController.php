<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgramEnrollment;
use App\Services\TrainingProgramService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingProgramController extends Controller
{
    public function __construct(
        private readonly TrainingProgramService $trainingProgramService
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_slug' => 'required|string',
            'starts_on' => 'required|date',
            'team_practice_band' => 'nullable|string',
        ]);

        $enrollment = $this->trainingProgramService->enroll(
            $request->user(),
            $validated['program_slug'],
            Carbon::parse($validated['starts_on']),
            $validated['team_practice_band'] ?? null,
        );

        $program = $this->trainingProgramService
            ->buildDashboardState($request->user(), $request->user()->now())['programs']
            ?? [];
        $programName = collect($program)
            ->firstWhere('slug', $validated['program_slug'])['name']
            ?? 'training program';
        $todayDay = $this->trainingProgramService->resolveDayForDate($enrollment, $request->user()->now());

        $message = "Started {$programName}.";

        if ($todayDay && ! ($todayDay['is_rest_day'] ?? false)) {
            $message .= ' Today: '.($todayDay['title'] ?? 'Practice Day').'.';
        } elseif ($todayDay) {
            $message .= ' Today is a recovery day.';
        }

        return redirect()->route('home', ['tab' => 'templates'])
            ->with('success', $message);
    }

    public function skip(Request $request, TrainingProgramEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'scheduled_for' => 'required|date',
        ]);

        $this->trainingProgramService->skipDay($enrollment, Carbon::parse($validated['scheduled_for']));

        return redirect()->route('home', ['tab' => 'templates']);
    }

    public function move(Request $request, TrainingProgramEnrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'scheduled_for' => 'required|date',
            'move_to' => 'required|date|after_or_equal:scheduled_for',
        ]);

        $this->trainingProgramService->moveDay(
            $enrollment,
            Carbon::parse($validated['scheduled_for']),
            Carbon::parse($validated['move_to'])
        );

        return redirect()->route('home', ['tab' => 'templates']);
    }
}
