<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    use HasFactory;

    protected $table = 'photos';

    protected $fillable = [
        'photo_album_id',
        'uploaded_by',
        'visibility', // public|members
        'path',
        'thumb_path',
        'caption',
        'alt_text',
        'taken_at',
        'enabled',
        'sort',
        'mime_type',
        'size_bytes',
        'width',
        'height',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'taken_at' => 'datetime',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(PhotoAlbum::class, 'photo_album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
