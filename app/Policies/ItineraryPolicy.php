<?php

namespace App\Policies;

use App\Models\Itinerary;
use App\Models\User;

class ItineraryPolicy
{
    public function manage(User $user, Itinerary $itinerary): bool
    {
        return $itinerary->user_id === $user->id;
    }
}
