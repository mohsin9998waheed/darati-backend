<?php

namespace App\Models;

use App\Services\S3Service;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'image_path', 'link_type', 'link_value', 'is_active', 'order',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order'     => 'integer',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return app(S3Service::class)->temporaryUrl($this->image_path, 60 * 48);
        }
        return null;
    }
}
