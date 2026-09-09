<?php

namespace App\Policies;

use App\Billing\PlanAccess;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function create(User $user): bool
    {
        return app(PlanAccess::class)->hasNotes($user);
    }

    public function update(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->id === $note->user_id;
    }
}
