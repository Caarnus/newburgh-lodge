<?php

namespace App\Models;

use App\Enums\MemberImportBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberImportBatch extends Model
{
    protected $fillable = [
        'import_type',
        'source_label',
        'original_filename',
        'stored_path',
        'status',
        'summary',
        'uploaded_by',
        'applied_at',
        'failed_at',
        'failure_message',
    ];

    protected $casts = [
        'status' => MemberImportBatchStatus::class,
        'summary' => 'array',
        'applied_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(MemberImportRow::class)->orderBy('row_number');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
