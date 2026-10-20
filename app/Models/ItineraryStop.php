<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryStop extends Model
{
    protected $fillable = ['location_id', 'position', 'visit_date', 'duration_minutes', 'notes'];

    protected function casts(): array
    {
        return ['visit_date' => 'date:Y-m-d'];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
