<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IndexMemberDirectoryRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberDirectoryController extends Controller
{
    public function index(IndexMemberDirectoryRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'member_type' => $request->string('member_type')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'page' => $request->integer('page') ?: 1,
            'per_page' => $request->integer('per_page') ?: 25,
        ];

        $records = $directoryService->paginateMembers($filters)
            ->through(fn ($person) => DirectoryPersonPresenter::member($person));

        return Inertia::render('Admin/MemberDirectory/Index', [
            'section' => 'members',
            'title' => 'Member Directory',
            'description' => 'Internal roster view with filters for status, type, and deceased visibility.',
            'filters' => $filters,
            'records' => $records,
            'statusOptions' => $directoryService->memberStatusOptions(),
            'memberTypeOptions' => $directoryService->memberTypeOptions(),
            'relationshipTypeOptions' => [],
            'sortOptions' => $directoryService->memberSortOptions(),
        ]);
    }

    public function export(IndexMemberDirectoryRequest $request, PeopleDirectoryService $directoryService): StreamedResponse
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'member_type' => $request->string('member_type')->toString() ?: null,
            'hide_deceased' => $request->boolean('hide_deceased'),
            'sort' => $request->string('sort')->toString() ?: 'name',
        ];

        $records = $directoryService->exportMembers($filters)
            ->map(fn ($person) => DirectoryPersonPresenter::member($person));

        return response()->streamDownload(function () use ($records) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Name',
                'Member Number',
                'Status',
                'Type',
                'Email',
                'Phone',
                'City',
                'State',
                'Deceased',
                'Death Date',
                'Last Contact',
            ]);

            foreach ($records as $row) {
                fputcsv($output, [
                    $row['display_name'],
                    $row['member_profile']['member_number'] ?? null,
                    $row['member_profile']['status'] ?? null,
                    $row['member_profile']['member_type'] ?? null,
                    $row['email'] ?? null,
                    $row['phone'] ?? null,
                    $row['city'] ?? null,
                    $row['state'] ?? null,
                    $row['is_deceased'] ? 'Yes' : 'No',
                    $row['death_date'] ?? null,
                    $row['last_contact_at'] ?? null,
                ]);
            }

            fclose($output);
        }, 'member-directory-export-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
