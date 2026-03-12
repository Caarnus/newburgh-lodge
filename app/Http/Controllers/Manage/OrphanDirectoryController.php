<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexOrphanDirectoryRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class OrphanDirectoryController extends Controller
{
    public function index(IndexOrphanDirectoryRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'page' => $request->integer('page') ?: 1,
            'per_page' => $request->integer('per_page') ?: 25,
        ];

        $records = $directoryService->paginateOrphans($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::orphan($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'orphans',
            'title' => 'Orphans',
            'description' => 'Children or dependents connected to deceased lodge members through tracked relationships.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'memberTypeOptions' => [],
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->careSortOptions(),
        ]);
    }
}
