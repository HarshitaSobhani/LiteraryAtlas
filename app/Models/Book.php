<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'first_publish_year', 'genre', 'open_library_key', 'cover_id',
    ];

    protected $appends = ['cover_url'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)
            ->withPivot(['relation', 'certainty', 'note']);
    }

    public function readingEntries(): HasMany
    {
        return $this->hasMany(ReadingEntry::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_id) {
            return null;
        }

        return rtrim(config('literaryatlas.covers_url'), '/')."/b/id/{$this->cover_id}-M.jpg";
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhereHas('authors', fn (Builder $a) => $a->where('name', 'like', $like));
        });
    }
}
