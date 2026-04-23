<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\S3Service;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'is_active',
        'bio',
        'city',
        'country',
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isArtist(): bool
    {
        return $this->role === 'artist';
    }

    public function isListener(): bool
    {
        return $this->role === 'listener';
    }

    public function audiobooks(): HasMany
    {
        return $this->hasMany(Audiobook::class, 'artist_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function listens(): HasMany
    {
        return $this->hasMany(Listen::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Avatars are non-sensitive profile images stored under avatars/.
            // We use a stable public URL (requires bucket policy: s3:GetObject on avatars/*).
            // This lets browsers and CDNs cache the image for up to 7 days, which is
            // critical for fast loading in mobile app lists (CachedNetworkImage, etc.).
            return app(S3Service::class)->url($this->avatar);
        }
        $initial   = strtoupper(substr($this->name, 0, 1));
        $encodedName = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$encodedName}&background=1DB954&color=fff&size=256";
    }
}
