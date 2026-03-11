<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberImport\StoreMemberRosterImportRequest;
use App\Models\MemberImportBatch;
use App\Services\People\Imports\MemberRosterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MemberRosterImportController extends Controller
{
    public function index(): JsonResponse
    {
        $batches = MemberImportBatch::query()
            ->withCount('rows')
            ->latest()
            ->paginate(10);

        return response()->json($batches);
    }

    public function store(StoreMemberRosterImportRequest $request, MemberRosterImportService $service): JsonResponse
    {
        $batch = $service->stageUploadedFile(
            file: $request->file('file'),
            uploadedBy: $request->user()?->id,
            sourceLabel: $request->string('source_label')->toString() ?: null,
        );

        return response()->json($batch->load('rows'), 201);
    }

    public function show(MemberImportBatch $batch): JsonResponse
    {
        return response()->json(
            $batch->load([
                'uploader',
                'rows.matchedPerson.memberProfile',
            ])
        );
    }

    /**
     * @throws Throwable
     */
    public function apply(Request $request, MemberImportBatch $importBatch, MemberRosterImportService $service): JsonResponse
    {
        abort_unless($request->user()?->can('manage members'), 403);

        $batch = $service->applyBatch(
            batch: $importBatch,
            includePossibleMatches: $request->boolean('include_possible_matches'),
        );
        return response()->json($batch->load('rows'));
    }
}
