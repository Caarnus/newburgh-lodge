<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhotoAlbum extends Model
{
    use HasFactory;

    protected $table = 'photo_albums';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'visibility', // public|members
        'enabled',
        'sort',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * Route model binding: /gallery/{album:slug}
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class, 'photo_album_id');
    }
}
