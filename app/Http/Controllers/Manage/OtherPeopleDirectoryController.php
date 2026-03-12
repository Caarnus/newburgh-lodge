<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexOtherPeopleDirectoryRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class OtherPeopleDirectoryController extends Controller
{
    public function index(IndexOtherPeopleDirectoryRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'page' => $request->integer('page') ?: 1,
            'per_page' => $request->integer('per_page') ?: 25,
        ];

        $records = $directoryService->paginateOtherPeople($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::member($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'others',
            'title' => 'Other People',
            'description' => 'People who are not currently categorized as members, widows, orphans, or relatives.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->peopleSortOptions(),
        ]);
    }
}
