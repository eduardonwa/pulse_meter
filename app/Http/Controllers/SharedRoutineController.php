<?php
namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use App\Models\SharedRoutine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SharedRoutineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('shared.index', [
            'canPublish' => $user->hasPaidProAccess(),
            'myRoutines' => $user->practiceRoutines()->with('sharedPublication')->orderBy('position')->get(),
            'published' => SharedRoutine::where('user_id', $user->id)->with('routine.steps')->get(),
            'imported' => $user->practiceRoutines()->whereNotNull('shared_routine_id')->with('importedFrom')->get(),
        ]);
    }

    public function store(Request $request, PracticeRoutine $practiceRoutine)
    {
        abort_unless($request->user()->hasPaidProAccess(), 403);
        abort_unless($practiceRoutine->user_id === $request->user()->id, 404);
        SharedRoutine::firstOrCreate(
            ['practice_routine_id' => $practiceRoutine->id],
            ['user_id' => $request->user()->id, 'token' => Str::random(48), 'is_active' => true]
        );
        return redirect()->route('account.my-routines');
    }

    public function toggle(Request $request, SharedRoutine $sharedRoutine)
    {
        abort_unless($sharedRoutine->user_id === $request->user()->id, 404);
        $sharedRoutine->is_active = ! $sharedRoutine->is_active;
        $sharedRoutine->save();
        return redirect()->route('account.my-routines');
    }
}
