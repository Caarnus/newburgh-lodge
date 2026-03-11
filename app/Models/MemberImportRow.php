<?php

namespace App\Models;

use App\Enums\MemberImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberImportRow extends Model
{
    protected $fillable = [
        'member_import_batch_id',
        'row_number',
        'row_hash',
        'status',
        'match_strategy',
        'matched_person_id',
        'matched_member_profile_id',
        'raw_payload',
        'normalized_payload',
        'review_notes',
        'error_message',
    ];

    protected $casts = [
        'status' => MemberImportRowStatus::class,
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MemberImportBatch::class, 'member_import_batch_id');
    }

    public function matchedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'matched_person_id');
    }

    public function matchedMemberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'matched_member_profile_id');
    }
}
