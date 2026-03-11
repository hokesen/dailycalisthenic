<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeditationLogRequest;
use App\Models\MeditationLog;
use Illuminate\Http\JsonResponse;

class MeditationController extends Controller
{
    public function store(StoreMeditationLogRequest $request): JsonResponse
    {
        $log = MeditationLog::query()->create([
            'user_id' => $request->user()->id,
            'duration_seconds' => $request->validated('duration_seconds'),
            'technique' => $request->validated('technique', 'breathing'),
            'breath_cycles_completed' => $request->validated('breath_cycles_completed'),
            'notes' => $request->validated('notes'),
            'practiced_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'duration_seconds' => $log->duration_seconds,
        ]);
    }
}
