<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasFactory;

    public const TYPES = [
        'story_setting' => 'Story setting',
        'landmark' => 'Landmark',
        'bookstore' => 'Bookstore',
        'institution' => 'Literary institution',
    ];

    protected $fillable = [
        'name', 'slug', 'type', 'country', 'city', 'latitude', 'longitude',
        'coordinates_verified', 'summary', 'significance', 'source_url',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'coordinates_verified' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)
            ->withPivot(['relation', 'certainty', 'note']);
    }

    public function scopeMappable(Builder $query): Builder
    {
        return $query->where('coordinates_verified', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('city', 'like', $like)
                ->orWhere('country', 'like', $like);
        });
    }
}
