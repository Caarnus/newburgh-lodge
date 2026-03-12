<?php

namespace App\Services\People\Imports;

use App\Enums\MemberImportBatchStatus;
use App\Enums\MemberImportRowStatus;
use App\Models\MemberImportBatch;
use App\Models\MemberImportRow;
use App\Models\MemberProfile;
use App\Models\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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

                $batch->update([
                    'status' => MemberImportBatchStatus::Staged,
                    'summary' => $this->buildSummary($batch->fresh('rows')),
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
    public function applyBatch(MemberImportBatch $batch, bool $includePossibleMatches = false): MemberImportBatch
    {
        DB::transaction(function () use ($batch, $includePossibleMatches) {
            $allowedStatuses = [
                MemberImportRowStatus::ExactMatch->value,
                MemberImportRowStatus::NewPerson->value,
            ];

            if ($includePossibleMatches) {
                $allowedStatuses[] = MemberImportRowStatus::PossibleMatch->value;
            }

            $rows = $batch->rows()->whereIn('status', $allowedStatuses)->get();

            foreach ($rows as $row) {
                $this->applyRow($row);
            }

            $batch->update([
                'status' => MemberImportBatchStatus::Applied,
                'applied_at' => now(),
                'summary' => $this->buildSummary($batch->fresh('rows')),
            ]);
        });

        return $batch->fresh('rows');
    }

    /**
     * @throws Throwable
     */
    public function applyRow(MemberImportRow $row): MemberImportRow
    {
        return DB::transaction(function () use ($row) {
            $data = $row->normalized_payload ?? [];

            if (empty($data)) {
                $row->update([
                    'status' => MemberImportRowStatus::Failed,
                    'error_message' => 'Missing normalized payload.',
                ]);

                return $row->fresh();
            }

            $person = $row->matchedPerson;

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
                    'is_deceased' => false,
                    'death_date' => $data['death_date'] ?? null,
                ]);
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
                'demit_date' => $data['demit_date'] ?? null,
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

            return $row->fresh(['matchedPerson', 'matchedMemberProfile']);
        });
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

        return $merged;
    }

    protected function filterNulls(array $values): array
    {
        return array_filter($values, static fn ($value) => $value !== null);
    }
}
