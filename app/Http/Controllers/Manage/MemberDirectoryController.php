<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexMemberDirectoryRequest;
use App\Services\People\Directory\DirectoryCsvExporter;
use App\Services\People\Directory\DirectoryFilterBuilder;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberDirectoryController extends Controller
{
    public function index(
        IndexMemberDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
    ): Response {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name');

        $records = $directoryService->paginateMembers($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::member($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'members',
            'title' => 'Member Directory',
            'description' => 'Internal roster view with filters for status and deceased visibility.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => $directoryService->memberStatusOptions(),
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->memberSortOptions(),
        ]);
    }

    public function export(
        IndexMemberDirectoryRequest $request,
        PeopleDirectoryService $directoryService,
        DirectoryFilterBuilder $filterBuilder,
        DirectoryCsvExporter $csvExporter,
    ): StreamedResponse {
        $filters = $filterBuilder->fromRequest($request, defaultSort: 'name', includePagination: false);

        $rows = $directoryService->exportMembers($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::member($person));

        return $csvExporter->download(
            prefix: 'member-directory-export',
            headers: [
                'Person ID',
                'Name',
                'Full Name',
                'First Name',
                'Middle Name',
                'Last Name',
                'Suffix',
                'Preferred Name',
                'Member Number',
                'Status',
                'Email',
                'Phone',
                'Address Line 1',
                'Address Line 2',
                'City',
                'State',
                'Postal Code',
                'Birth Date',
                'Deceased',
                'Death Date',
                'Directory Visible',
                'Auto Match Registration',
                'EA Date',
                'FC Date',
                'MM Date',
                'Demit Date',
                'Roster Import Source',
                'Last Imported At',
                'Notes',
                'Last Contact',
            ],
            rows: $rows,
            mapRow: fn (array $row) => [
                $row['id'],
                $row['display_name'],
                $row['full_name'] ?? null,
                $row['first_name'] ?? null,
                $row['middle_name'] ?? null,
                $row['last_name'] ?? null,
                $row['suffix'] ?? null,
                $row['preferred_name'] ?? null,
                $row['member_profile']['member_number'] ?? null,
                $row['member_profile']['status'] ?? null,
                $row['email'] ?? null,
                $row['phone'] ?? null,
                $row['address_line_1'] ?? null,
                $row['address_line_2'] ?? null,
                $row['city'] ?? null,
                $row['state'] ?? null,
                $row['postal_code'] ?? null,
                $row['birth_date'] ?? null,
                $row['is_deceased'] ? 'Yes' : 'No',
                $row['death_date'] ?? null,
                isset($row['member_profile']) ? (($row['member_profile']['directory_visible'] ?? false) ? 'Yes' : 'No') : null,
                isset($row['member_profile']) ? (($row['member_profile']['can_auto_match_registration'] ?? false) ? 'Yes' : 'No') : null,
                $row['member_profile']['ea_date'] ?? null,
                $row['member_profile']['fc_date'] ?? null,
                $row['member_profile']['mm_date'] ?? null,
                $row['member_profile']['demit_date'] ?? null,
                $row['member_profile']['roster_import_source'] ?? null,
                $row['member_profile']['last_imported_at'] ?? null,
                $row['notes'] ?? null,
                $row['last_contact_at'] ?? null,
            ],
        );
    }
}
