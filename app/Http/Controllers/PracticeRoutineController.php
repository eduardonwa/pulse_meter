<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use Illuminate\Http\Request;

class PracticeRoutineController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->practiceRoutines()
            ->with('steps')
            ->get();
    }

    public function store(Request $request)
    {
        // Lo implementaremos cuando habilitemos
        // múltiples rutinas para Pro.
    }

    public function update(
        Request $request,
        PracticeRoutine $practiceRoutine
    ) {
        // Rename, position, etc.
    }

    public function destroy(
        PracticeRoutine $practiceRoutine
    ) {
        // Eliminar rutina Pro.
    }
}