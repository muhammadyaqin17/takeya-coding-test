<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_draft',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_draft' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include published posts.
     * Published: is_draft = false AND published_at <= now()
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_draft', false)
            ->where('published_at', '<=', now());
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return ! $this->is_draft && $this->published_at && $this->published_at->lte(now());
    }

    /**
     * Check if the post is a draft.
     */
    public function isDraft(): bool
    {
        return $this->is_draft;
    }

    /**
     * Check if the post is scheduled.
     */
    public function isScheduled(): bool
    {
        return ! $this->is_draft && $this->published_at && $this->published_at->gt(now());
    }
}
