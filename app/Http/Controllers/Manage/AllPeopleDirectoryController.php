<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexAllPeopleDirectoryRequest;
use App\Services\People\Directory\DirectoryCsvExporter;
use App\Services\People\Directory\DirectoryFilterBuilder;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllPeopleDirectoryController extends Controller
{
    public function index(
        IndexAllPeopleDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
    ): Response {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name');

        $records = $directoryService->paginateAllPeople($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::member($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'all',
            'title' => 'All People',
            'description' => 'Complete internal people directory across all classifications.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->peopleSortOptions(),
        ]);
    }

    public function export(
        IndexAllPeopleDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryCsvExporter $csvExporter,
    ): StreamedResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        $rows = $directoryService->exportAllPeople($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::member($person));

        return $csvExporter->download(
            prefix: 'all-people-directory-export',
            headers: [
                'Name',
                'Member Number',
                'Status',
                'Email',
                'Phone',
                'City',
                'State',
                'Deceased',
                'Death Date',
                'Last Contact',
            ],
            rows: $rows,
            mapRow: fn (array $row) => [
                $row['display_name'],
                $row['member_profile']['member_number'] ?? null,
                $row['member_profile']['status'] ?? null,
                $row['email'] ?? null,
                $row['phone'] ?? null,
                $row['city'] ?? null,
                $row['state'] ?? null,
                $row['is_deceased'] ? 'Yes' : 'No',
                $row['death_date'] ?? null,
                $row['last_contact_at'] ?? null,
            ],
        );
    }
}
