<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $request->user()->notes()->create($request->validated());

        return back();
    }

    public function archive(Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->forceFill(['archived_at' => now()])->save();

        return back();
    }

    public function restore(Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->forceFill(['archived_at' => null])->save();

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back();
    }
}
