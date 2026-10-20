<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingEntry extends Model
{
    use HasFactory;

    public const STATUSES = [
        'want_to_read' => 'Want to read',
        'reading' => 'Reading',
        'completed' => 'Completed',
        'on_hold' => 'On hold',
    ];

    protected $fillable = ['user_id', 'book_id', 'status', 'progress', 'rating', 'notes', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'rating' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
