<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexOtherPeopleDirectoryRequest;
use App\Services\People\Directory\DirectoryAddressLabelExporter;
use App\Services\People\Directory\DirectoryCsvExporter;
use App\Services\People\Directory\DirectoryFilterBuilder;
use App\Services\People\Directory\PeopleDirectoryService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OtherPeopleDirectoryController extends Controller
{
    public function index(
        IndexOtherPeopleDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
    ): Response {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name');

        $records = $directoryService->paginateOtherPeople($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::member($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'others',
            'title' => 'Other People',
            'description' => 'People who are not currently categorized as members, widows, orphans, or relatives.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => $directoryService->memberStatusOptions(),
            'defaultStatusFilters' => $directoryService->defaultMemberStatusFilters(),
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->peopleSortOptions(),
        ]);
    }

    public function export(
        IndexOtherPeopleDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryCsvExporter $csvExporter,
    ): StreamedResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        $rows = $directoryService->exportOtherPeople($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::member($person));

        return $csvExporter->download(
            prefix: 'other-people-directory-export',
            headers: [
                'Name',
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

    public function addressLabels(
        IndexOtherPeopleDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryAddressLabelExporter $labelExporter,
    ): HttpResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        return $labelExporter->download(
            prefix: 'other-people-address-labels',
            people: $directoryService->exportOtherPeople($filters),
        );
    }
}
