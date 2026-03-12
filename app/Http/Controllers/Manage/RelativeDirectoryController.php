<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexRelativeDirectoryRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class RelativeDirectoryController extends Controller
{
    public function index(IndexRelativeDirectoryRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'relationship_type' => $request->string('relationship_type')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'page' => $request->integer('page') ?: 1,
            'per_page' => $request->integer('per_page') ?: 25,
        ];

        $records = $directoryService->paginateRelatives($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::relative($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'relatives',
            'title' => 'Relatives',
            'description' => 'Related people who are not currently surfaced in the widow or orphan derived views.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'memberTypeOptions' => [],
            'relationshipTypeOptions' => $directoryService->relationshipTypeOptions(),
            'sortOptions' => $directoryService->relativeSortOptions(),
        ]);
    }
}
