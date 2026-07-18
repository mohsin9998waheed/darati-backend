<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Services\S3Service;
use Throwable;

class Audiobook extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'author_name',
        'artist_id',
        'category_id',
        'language',
        'status',
        'rejection_reason',
        'total_listens',
        'total_play_seconds',
        'avg_rating',
        'is_trending',
    ];

    protected $appends = ['thumbnail_url'];

    protected function casts(): array
    {
        return [
            'avg_rating'    => 'float',
            'total_listens'      => 'integer',
            'total_play_seconds' => 'integer',
            'is_trending'   => 'boolean',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function episodes(): HasManyThrough
    {
        return $this->hasManyThrough(Episode::class, Chapter::class);
    }

    /**
     * Product model is Book → Episodes. Chapters remain as a hidden
     * one-per-book wrapper for FK / stream / cycle compatibility.
     */
    public function ensureDefaultChapter(): Chapter
    {
        $chapter = $this->chapters()->orderBy('order')->orderBy('id')->first();

        if ($chapter) {
            return $chapter;
        }

        return $this->chapters()->create([
            'title' => 'Episodes',
            'order' => 1,
        ]);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            try {
                // Signed URL so we don't require public bucket access (Block Public Access compatible).
                return app(S3Service::class)->temporaryUrl($this->thumbnail, 60 * 48); // 48 hours
            } catch (Throwable) {
                // Fail open: one bad object key or S3 config issue should not 500
                // the whole /audiobooks response.
                return asset('images/audiobook-placeholder.png');
            }
        }
        return asset('images/audiobook-placeholder.png');
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
