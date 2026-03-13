<?php

namespace App\Services\People\Imports;

use App\Enums\MemberImportBatchStatus;
use App\Enums\MemberImportRowStatus;
use App\Enums\MemberStatus;
use App\Helpers\Audit;
use App\Models\MemberImportBatch;
use App\Models\MemberImportRow;
use App\Models\MemberProfile;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MemberRosterImportService
{
    public function __construct(
        protected MemberRosterSpreadsheetService $parser,
    ) {
    }

    public function stageUploadedFile(UploadedFile $file, ?int $uploadedBy = null, ?string $sourceLabel = null): MemberImportBatch
    {
        $storedPath = $file->store('member-imports');

        return $this->stagePath(
            absolutePath: Storage::path($storedPath),
            originalFilename: $file->getClientOriginalName(),
            storedPath: $storedPath,
            uploadedBy: $uploadedBy,
            sourceLabel: $sourceLabel,
        );
    }

    /**
     * @throws Throwable
     */
    public function stagePath(string $absolutePath, string $originalFilename, string $storedPath, ?int $uploadedBy = null, ?string $sourceLabel = null): MemberImportBatch
    {
        $batch = MemberImportBatch::create([
            'import_type' => 'roster',
            'source_label' => $sourceLabel,
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'status' => MemberImportBatchStatus::Uploaded,
            'uploaded_by' => $uploadedBy,
        ]);

        try {
            $rows = $this->parser->parse($absolutePath);

            DB::transaction(function () use ($batch, $rows) {
                foreach ($rows as $row) {
                    $match = $this->determineMatch($row['normalized']);

                    MemberImportRow::create([
                        'member_import_batch_id' => $batch->id,
                        'row_number' => $row['row_number'],
                        'row_hash' => hash('sha256', json_encode($row['normalized'])),
                        'status' => $match['status'],
                        'match_strategy' => $match['match_strategy'] ?? $match['strategy'] ?? null,
                        'matched_person_id' => $match['matched_person_id'],
                        'matched_member_profile_id' => $match['matched_member_profile_id'],
                        'raw_payload' => $row['raw'],
                        'normalized_payload' => $row['normalized'],
                    ]);
                }

                $batch->refresh()->load('rows');
                $this->flagDuplicateMemberNumbers($batch, includePossibleMatches: true);

                $batch->update([
                    'status' => MemberImportBatchStatus::Staged,
                    'summary' => $this->buildSummary($batch->fresh('rows')),
                    'failed_at' => null,
                    'failure_message' => null,
                ]);
            });
        } catch (Throwable $exception) {
            $batch->update([
                'status' => MemberImportBatchStatus::Failed,
                'failed_at' => now(),
                'failure_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $batch->fresh('rows');
    }

    /**
     * @throws Throwable
     */
    public function applyBatch(MemberImportBatch $batch, bool $includePossibleMatches = false, ?int $actorId = null): MemberImportBatch
    {
        $batch = $batch->fresh() ?? $batch;
        $batch->load('rows');

        $duplicateRowIds = $this->flagDuplicateMemberNumbers($batch, $includePossibleMatches);

        $rows = $batch->rows()
            ->whereIn('status', $this->applyCandidateStatuses($includePossibleMatches))
            ->when(
                ! empty($duplicateRowIds),
                fn ($query) => $query->whereNotIn('id', $duplicateRowIds)
            )
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            try {
                $this->applyRow($row, $actorId);
            } catch (Throwable $exception) {
                $this->markRowAsFailed($row, $this->friendlyApplyErrorMessage($exception));
            }
        }

        $refreshedBatch = $batch->fresh('rows');
        $summary = $this->buildSummary($refreshedBatch);
        $failedCount = (int) ($summary['failed'] ?? 0);

        $refreshedBatch->update([
            'status' => $failedCount > 0
                ? MemberImportBatchStatus::Failed
                : MemberImportBatchStatus::Applied,
            'applied_at' => now(),
            'failed_at' => $failedCount > 0 ? now() : null,
            'failure_message' => $failedCount > 0
                ? "{$failedCount} row(s) failed during apply. Review and edit failed rows, then apply again."
                : null,
            'summary' => $summary,
        ]);

        return $refreshedBatch->fresh('rows');
    }

    /**
     * @throws Throwable
     */
    public function applyRow(MemberImportRow $row, ?int $actorId = null): MemberImportRow
    {
        return DB::transaction(function () use ($row, $actorId) {
            $data = $row->normalized_payload ?? [];
            $beforeStatus = $row->status?->value ?? (string) $row->status;
            $beforeMatchedPersonId = $row->matched_person_id;
            $beforeMatchedMemberProfileId = $row->matched_member_profile_id;

            if (empty($data)) {
                $row->update([
                    'status' => MemberImportRowStatus::Failed,
                    'error_message' => 'Missing normalized payload.',
                ]);

                return $row->fresh();
            }

            $person = $row->matchedPerson;
            $personCreated = false;

            if (!$person) {
                $person = Person::create([
                    'first_name' => $data['first_name'] ?? null,
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address_line_1' => $data['address_line_1'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'is_deceased' => (bool) ($data['is_deceased'] ?? false),
                    'death_date' => $data['death_date'] ?? null,
                ]);
                $personCreated = true;
            } else {
                $person->fill($this->mergeIntoExistingPerson($person, $data));
                $person->save();
            }

            $profile = $person->memberProfile ?: new MemberProfile(['person_id' => $person->id]);

            $profile->fill($this->filterNulls([
                'member_number' => $data['member_number'] ?? null,
                'status' => $data['status'] ?? null,
                'ea_date' => $data['ea_date'] ?? null,
                'fc_date' => $data['fc_date'] ?? null,
                'mm_date' => $data['mm_date'] ?? null,
                'honorary_date' => $data['honorary_date'] ?? null,
                'demit_date' => $data['demit_date'] ?? null,
                'past_master' => $data['past_master'] ?? null,
                'roster_import_source' => $row->batch->source_label ?: $row->batch->original_filename,
                'last_imported_at' => now(),
            ]));
            $profile->person()->associate($person);
            $profile->save();

            $row->update([
                'matched_person_id' => $person->id,
                'matched_member_profile_id' => $profile->id,
                'status' => MemberImportRowStatus::Applied,
                'error_message' => null,
            ]);

            $fresh = $row->fresh(['matchedPerson', 'matchedMemberProfile']);

            Audit::logForActor(
                actorId: $actorId,
                action: 'member_import.row_applied',
                subject: $fresh,
                changes: [
                    'before' => [
                        'status' => $beforeStatus,
                        'matched_person_id' => $beforeMatchedPersonId,
                        'matched_member_profile_id' => $beforeMatchedMemberProfileId,
                    ],
                    'after' => [
                        'status' => MemberImportRowStatus::Applied->value,
                        'matched_person_id' => $fresh->matched_person_id,
                        'matched_member_profile_id' => $fresh->matched_member_profile_id,
                    ],
                ],
                meta: [
                    'batch_id' => $fresh->member_import_batch_id,
                    'row_number' => $fresh->row_number,
                    'match_strategy' => $fresh->match_strategy,
                    'resolution' => $personCreated ? 'created_person' : 'matched_existing_person',
                ],
                secondary: $person,
            );

            return $fresh;
        });
    }

    public function updateReviewedRow(MemberImportRow $row, array $input): MemberImportRow
    {
        $row->loadMissing('batch');
        $current = $row->normalized_payload ?? [];

        $normalized = $this->mergeNormalizedPayloadFromReview($current, $input);
        $match = $this->determineMatch($normalized);

        $row->update([
            'normalized_payload' => $normalized,
            'row_hash' => hash('sha256', json_encode($normalized)),
            'status' => $match['status'],
            'match_strategy' => $match['match_strategy'] ?? $match['strategy'] ?? null,
            'matched_person_id' => $match['matched_person_id'],
            'matched_member_profile_id' => $match['matched_member_profile_id'],
            'review_notes' => Arr::exists($input, 'review_notes')
                ? $this->normalizeString($input['review_notes'] ?? null)
                : $row->review_notes,
            'error_message' => null,
        ]);

        if ($row->batch) {
            $batch = $row->batch->fresh() ?? $row->batch;
            $batch->load('rows');
            $this->flagDuplicateMemberNumbers($batch, includePossibleMatches: true);

            $batch->update([
                'status' => MemberImportBatchStatus::Staged,
                'applied_at' => null,
                'failed_at' => null,
                'failure_message' => null,
                'summary' => $this->buildSummary($batch->fresh('rows')),
            ]);
        }

        return $row->fresh(['batch', 'matchedPerson.memberProfile', 'matchedMemberProfile']);
    }

    protected function determineMatch(array $data): array
    {
        if (!Arr::get($data, 'first_name') || !Arr::get($data, 'last_name')) {
            return [
                'status' => MemberImportRowStatus::Ignored,
                'match_strategy' => 'missing_name',
                'matched_person_id' => null,
                'matched_member_profile_id' => null,
            ];
        }

        $memberNumber = Arr::get($data, 'member_number');

        if ($memberNumber) {
            $profile = MemberProfile::query()
                ->with('person')
                ->where('member_number', $memberNumber)
                ->first();

            if ($profile) {
                return [
                    'status' => MemberImportRowStatus::ExactMatch,
                    'match_strategy' => 'member_number',
                    'matched_person_id' => $profile->person_id,
                    'matched_member_profile_id' => $profile->id,
                ];
            }
        }

        $email = Arr::get($data, 'email');

        if ($email) {
            $person = Person::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->first();

            if ($person) {
                return [
                    'status' => MemberImportRowStatus::ExactMatch,
                    'match_strategy' => 'email',
                    'matched_person_id' => $person->id,
                    'matched_member_profile_id' => $person->memberProfile?->id,
                ];
            }
        }

        $birthDate = Arr::get($data, 'birth_date');

        if ($birthDate) {
            $possible = Person::query()
                ->whereRaw('LOWER(first_name) = ?', [Str::lower((string) Arr::get($data, 'first_name'))])
                ->whereRaw('LOWER(last_name) = ?', [Str::lower((string) Arr::get($data, 'last_name'))])
                ->whereDate('birth_date', $birthDate)
                ->first();

            if ($possible) {
                return [
                    'status' => MemberImportRowStatus::PossibleMatch,
                    'match_strategy' => 'name_birth_date',
                    'matched_person_id' => $possible->id,
                    'matched_member_profile_id' => $possible->memberProfile?->id,
                ];
            }
        }

        return [
            'status' => MemberImportRowStatus::NewPerson,
            'match_strategy' => 'none',
            'matched_person_id' => null,
            'matched_member_profile_id' => null,
        ];
    }

    protected function buildSummary(MemberImportBatch $batch): array
    {
        $rows = $batch->rows;

        return [
            'total_rows' => $rows->count(),
            'exact_matches' => $rows->where('status', MemberImportRowStatus::ExactMatch)->count(),
            'possible_matches' => $rows->where('status', MemberImportRowStatus::PossibleMatch)->count(),
            'new_people' => $rows->where('status', MemberImportRowStatus::NewPerson)->count(),
            'ignored' => $rows->where('status', MemberImportRowStatus::Ignored)->count(),
            'applied' => $rows->where('status', MemberImportRowStatus::Applied)->count(),
            'failed' => $rows->where('status', MemberImportRowStatus::Failed)->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function applyCandidateStatuses(bool $includePossibleMatches): array
    {
        $statuses = [
            MemberImportRowStatus::ExactMatch->value,
            MemberImportRowStatus::NewPerson->value,
            MemberImportRowStatus::Failed->value,
        ];

        if ($includePossibleMatches) {
            $statuses[] = MemberImportRowStatus::PossibleMatch->value;
        }

        return $statuses;
    }

    /**
     * @return array<int, int>
     */
    protected function flagDuplicateMemberNumbers(MemberImportBatch $batch, bool $includePossibleMatches): array
    {
        $rows = $batch->rows;
        $candidateStatuses = $this->applyCandidateStatuses($includePossibleMatches);

        /** @var Collection<int, Collection<int, MemberImportRow>> $duplicates */
        $duplicates = $rows
            ->filter(function (MemberImportRow $row) use ($candidateStatuses) {
                $status = $row->status?->value ?? (string) $row->status;
                return in_array($status, $candidateStatuses, true)
                    && filled($this->normalizeString(data_get($row->normalized_payload, 'member_number')));
            })
            ->groupBy(function (MemberImportRow $row) {
                return Str::lower((string) data_get($row->normalized_payload, 'member_number'));
            })
            ->filter(fn (Collection $group) => $group->count() > 1);

        $duplicateRowIds = [];

        foreach ($duplicates as $memberNumber => $group) {
            $displayMemberNumber = (string) data_get($group->first()?->normalized_payload, 'member_number', $memberNumber);
            $errorMessage = "Duplicate member ID in this batch: {$displayMemberNumber}. Edit one or more rows before applying.";

            foreach ($group as $row) {
                $this->markRowAsFailed($row, $errorMessage);
                $duplicateRowIds[] = $row->id;
            }
        }

        return array_values(array_unique($duplicateRowIds));
    }

    protected function markRowAsFailed(MemberImportRow $row, string $errorMessage): void
    {
        $row->update([
            'status' => MemberImportRowStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    protected function friendlyApplyErrorMessage(Throwable $exception): string
    {
        $message = trim((string) $exception->getMessage());
        $lower = Str::lower($message);

        if (Str::contains($lower, ['member_number', 'member profile', 'duplicate entry', 'unique'])) {
            return 'Duplicate member ID/member number detected. Update failed rows and apply again.';
        }

        return Str::limit($message !== '' ? $message : 'Row apply failed for an unknown reason.', 1000);
    }

    protected function mergeNormalizedPayloadFromReview(array $current, array $input): array
    {
        $next = $current;

        foreach ([
            'member_number',
            'status',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'preferred_name',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'phone',
            'email',
            'spouse_name',
            'full_name_source',
        ] as $field) {
            if (Arr::exists($input, $field)) {
                $next[$field] = $field === 'email'
                    ? $this->normalizeEmail($input[$field] ?? null)
                    : $this->normalizeString($input[$field] ?? null);
            }
        }

        foreach ([
            'birth_date',
            'ea_date',
            'fc_date',
            'mm_date',
            'honorary_date',
            'demit_date',
            'death_date',
        ] as $field) {
            if (Arr::exists($input, $field)) {
                $next[$field] = $this->normalizeDate($input[$field] ?? null);
            }
        }

        foreach (['past_master', 'is_deceased'] as $field) {
            if (Arr::exists($input, $field)) {
                $next[$field] = $this->normalizeBoolean($input[$field] ?? null);
            }
        }

        if (Arr::exists($input, 'status')) {
            $next['status'] = $this->normalizeStatus($input['status'] ?? null);
        }

        if (($next['status'] ?? null) === MemberStatus::Deceased->value) {
            $next['is_deceased'] = true;
        }

        if (($next['is_deceased'] ?? null) === false && Arr::exists($input, 'is_deceased')) {
            $next['death_date'] = null;
        }

        return $next;
    }

    protected function mergeIntoExistingPerson(Person $person, array $data): array
    {
        $merged = [];

        foreach ([
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'email',
            'phone',
            'address_line_1',
            'city',
            'state',
            'postal_code',
            'birth_date',
        ] as $key) {
            $incoming = Arr::get($data, $key);

            if ($incoming !== null && blank($person->{$key})) {
                $merged[$key] = $incoming;
            }
        }

        if ($this->incomingMarksDeceased($data) && ! $person->is_deceased) {
            $merged['is_deceased'] = true;
        }

        $incomingDeathDate = Arr::get($data, 'death_date');
        if ($incomingDeathDate !== null && blank($person->death_date)) {
            $merged['death_date'] = $incomingDeathDate;
        }

        return $merged;
    }

    protected function filterNulls(array $values): array
    {
        return array_filter($values, static fn ($value) => $value !== null);
    }

    protected function incomingMarksDeceased(array $data): bool
    {
        return (bool) Arr::get($data, 'is_deceased', false)
            || Arr::get($data, 'status') === MemberStatus::Deceased->value;
    }

    protected function normalizeStatus(mixed $value): ?string
    {
        $status = $this->normalizeString($value);

        if (! $status) {
            return null;
        }

        return in_array($status, MemberStatus::values(), true) ? $status : null;
    }

    protected function normalizeString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        return $value ? Str::lower($value) : null;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        $normalized = Str::lower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'pm', 'past master'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        return null;
    }
}
