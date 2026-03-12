<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexRelativeDirectoryRequest;
use App\Services\People\Directory\DirectoryCsvExporter;
use App\Services\People\Directory\DirectoryFilterBuilder;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelativeDirectoryController extends Controller
{
    public function index(
        IndexRelativeDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
    ): Response {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name');

        $records = $directoryService->paginateRelatives($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::relative($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'relatives',
            'title' => 'Relatives',
            'description' => 'Related people who are not currently surfaced in the widow or orphan derived views.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'relationshipTypeOptions' => $directoryService->relationshipTypeOptions(),
            'sortOptions' => $directoryService->relativeSortOptions(),
        ]);
    }

    public function export(
        IndexRelativeDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryCsvExporter $csvExporter,
    ): StreamedResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        $rows = $directoryService->exportRelatives($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::relative($person));

        return $csvExporter->download(
            prefix: 'relative-directory-export',
            headers: [
                'Name',
                'Relationship',
                'Related To',
                'Related Member #',
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
                $row['relationship']['label'] ?? null,
                $row['relationship']['related_person']['display_name'] ?? null,
                $row['relationship']['related_person']['member_number'] ?? null,
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
