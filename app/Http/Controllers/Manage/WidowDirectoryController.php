<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexWidowDirectoryRequest;
use App\Services\People\Directory\DirectoryCsvExporter;
use App\Services\People\Directory\DirectoryFilterBuilder;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WidowDirectoryController extends Controller
{
    public function index(
        IndexWidowDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
    ): Response {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name');

        $records = $directoryService->paginateWidows($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::widow($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'widows',
            'title' => 'Widows',
            'description' => 'Surviving spouses connected to deceased lodge members through spouse relationships.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => [],
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->careSortOptions(),
        ]);
    }

    public function export(
        IndexWidowDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryCsvExporter $csvExporter,
    ): StreamedResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        $rows = $directoryService->exportWidows($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::widow($person));

        return $csvExporter->download(
            prefix: 'widow-directory-export',
            headers: [
                'Name',
                'Email',
                'Phone',
                'City',
                'State',
                'Related Member',
                'Related Member #',
                'Related Death Date',
                'Deceased',
                'Death Date',
                'Last Contact',
            ],
            rows: $rows,
            mapRow: fn (array $row) => [
                $row['display_name'],
                $row['email'] ?? null,
                $row['phone'] ?? null,
                $row['city'] ?? null,
                $row['state'] ?? null,
                $row['related_member']['display_name'] ?? null,
                $row['related_member']['member_number'] ?? null,
                $row['related_member']['death_date'] ?? null,
                $row['is_deceased'] ? 'Yes' : 'No',
                $row['death_date'] ?? null,
                $row['last_contact_at'] ?? null,
            ],
        );
    }
}
