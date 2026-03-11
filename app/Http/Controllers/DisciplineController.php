<?php

namespace App\Http\Controllers;

use App\Services\TrainingCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DisciplineController extends Controller
{
    public function __invoke(Request $request, TrainingCatalogService $catalogService): RedirectResponse
    {
        $validated = $request->validate([
            'discipline' => 'required|string',
        ]);

        abort_unless(array_key_exists($validated['discipline'], $catalogService->getDisciplines()), 404);

        $request->session()->put('selected_discipline', $validated['discipline']);

        return redirect()->back();
    }
}
