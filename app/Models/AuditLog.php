<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id','actor_type','actor_guard',
        'action',
        'subject_type','subject_id',
        'secondary_subject_type','secondary_subject_id',
        'before','after','meta',
        'ip','user_agent','request_id',
        'succeeded','error_message',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'meta' => 'array',
            'succeeded' => 'boolean',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
