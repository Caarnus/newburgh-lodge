<?php

namespace App\Http\Controllers\Manage;

use App\Enums\MemberImportBatchStatus;
use App\Enums\MemberImportRowStatus;
use App\Helpers\Audit;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\MemberImport\StoreMemberRosterImportRequest;
use App\Models\MemberImportBatch;
use App\Models\MemberImportRow;
use App\Services\People\Imports\MemberRosterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MemberRosterImportController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        abort_unless($request->user()?->can(PeoplePermissions::IMPORT_MEMBER_ROSTER), 403);

        if ($request->expectsJson()) {
            $batches = MemberImportBatch::query()
                ->withCount('rows')
                ->latest()
                ->paginate(10);

            return response()->json($batches);
        }

        $selectedBatch = null;
        $selectedRows = collect();
        $selectedBatchId = $request->integer('batch');

        if ($selectedBatchId > 0) {
            $selectedBatch = MemberImportBatch::query()
                ->withCount('rows')
                ->findOrFail($selectedBatchId);

            $selectedRows = MemberImportRow::query()
                ->with('matchedPerson.memberProfile')
                ->where('member_import_batch_id', $selectedBatch->id)
                ->orderBy('row_number')
                ->limit(500)
                ->get();
        }

        return Inertia::render('Admin/MemberDirectory/Imports', [
            'batches' => MemberImportBatch::query()
                ->withCount('rows')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (MemberImportBatch $batch) => $this->serializeBatch($batch))
                ->values(),
            'selectedBatch' => $selectedBatch ? $this->serializeBatch($selectedBatch) : null,
            'selectedRows' => $selectedRows
                ->map(fn (MemberImportRow $row) => $this->serializeRow($row))
                ->values(),
            'maxRowsShown' => 500,
        ]);
    }

    public function store(StoreMemberRosterImportRequest $request, MemberRosterImportService $service): JsonResponse|RedirectResponse
    {
        Audit::log($request, 'member_import.started', meta: [
            'original_filename' => $request->file('file')?->getClientOriginalName(),
            'source_label' => $request->string('source_label')->toString() ?: null,
        ]);

        try {
            $batch = $service->stageUploadedFile(
                file: $request->file('file'),
                uploadedBy: $request->user()?->id,
                sourceLabel: $request->string('source_label')->toString() ?: null,
            );
        } catch (Throwable $exception) {
            Audit::log(
                $request,
                'member_import.stage_failed',
                meta: [
                    'original_filename' => $request->file('file')?->getClientOriginalName(),
                    'source_label' => $request->string('source_label')->toString() ?: null,
                ],
                succeeded: false,
                errorMessage: $exception->getMessage(),
            );

            throw $exception;
        }

        Audit::log($request, 'member_import.staged', $batch, meta: [
            'summary' => $batch->summary ?? [],
            'row_count' => $batch->rows()->count(),
        ]);

        if ($request->expectsJson()) {
            return response()->json($batch->load('rows'), 201);
        }

        return redirect()
            ->route('manage.member-directory.imports.index', ['batch' => $batch->id])
            ->with('success', 'Roster file uploaded and staged for review.');
    }

    public function show(Request $request, MemberImportBatch $importBatch): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can(PeoplePermissions::IMPORT_MEMBER_ROSTER), 403);

        if ($request->expectsJson()) {
            return response()->json(
                $importBatch->load([
                    'uploader',
                    'rows.matchedPerson.memberProfile',
                ])
            );
        }

        return redirect()->route('manage.member-directory.imports.index', ['batch' => $importBatch->id]);
    }

    /**
     * @throws Throwable
     */
    public function apply(Request $request, MemberImportBatch $importBatch, MemberRosterImportService $service): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can(PeoplePermissions::IMPORT_MEMBER_ROSTER), 403);

        try {
            $batch = $service->applyBatch(
                batch: $importBatch,
                includePossibleMatches: $request->boolean('include_possible_matches'),
                actorId: $request->user()?->id,
            );
        } catch (Throwable $exception) {
            Audit::log(
                $request,
                'member_import.apply_failed',
                $importBatch,
                meta: [
                    'include_possible_matches' => $request->boolean('include_possible_matches'),
                ],
                succeeded: false,
                errorMessage: $exception->getMessage(),
            );

            throw $exception;
        }

        Audit::log($request, 'member_import.applied', $batch, meta: [
            'include_possible_matches' => $request->boolean('include_possible_matches'),
            'summary' => $batch->summary ?? [],
        ]);

        if ($request->expectsJson()) {
            return response()->json($batch->load('rows'));
        }

        return redirect()
            ->route('manage.member-directory.imports.index', ['batch' => $batch->id])
            ->with('success', 'Import batch applied successfully.');
    }

    protected function serializeBatch(MemberImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'import_type' => $batch->import_type,
            'source_label' => $batch->source_label,
            'original_filename' => $batch->original_filename,
            'status' => $batch->status instanceof MemberImportBatchStatus
                ? $batch->status->value
                : (string) $batch->status,
            'summary' => $batch->summary ?? [],
            'rows_count' => $batch->rows_count ?? 0,
            'uploaded_by' => $batch->uploaded_by,
            'created_at' => optional($batch->created_at)?->toDateTimeString(),
            'applied_at' => optional($batch->applied_at)?->toDateTimeString(),
            'failed_at' => optional($batch->failed_at)?->toDateTimeString(),
            'failure_message' => $batch->failure_message,
        ];
    }

    protected function serializeRow(MemberImportRow $row): array
    {
        return [
            'id' => $row->id,
            'row_number' => $row->row_number,
            'status' => $row->status instanceof MemberImportRowStatus
                ? $row->status->value
                : (string) $row->status,
            'match_strategy' => $row->match_strategy,
            'error_message' => $row->error_message,
            'normalized_payload' => $row->normalized_payload ?? [],
            'matched_person' => $row->matchedPerson ? [
                'id' => $row->matchedPerson->id,
                'display_name' => $row->matchedPerson->display_name,
                'member_number' => $row->matchedPerson->memberProfile?->member_number,
            ] : null,
        ];
    }
}
