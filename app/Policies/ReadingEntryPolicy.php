<?php

namespace App\Policies;

use App\Models\ReadingEntry;
use App\Models\User;

class ReadingEntryPolicy
{
    public function manage(User $user, ReadingEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
