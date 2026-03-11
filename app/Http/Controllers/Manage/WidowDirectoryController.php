<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexWidowDirectoryRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class WidowDirectoryController extends Controller
{
    public function index(IndexWidowDirectoryRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'page' => $request->integer('page') ?: 1,
            'per_page' => $request->integer('per_page') ?: 25,
        ];

        $widows = $directoryService->paginateWidows($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::widow($person));

        return Inertia::render('Admin/MemberDirectory/Widows', [
            'filters' => $filters,
            'widows' => $widows,
            'sortOptions' => $directoryService->careSortOptions(),
        ]);
    }
}
