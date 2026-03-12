<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\ShowSelfPersonProfileRequest;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class MemberSelfProfileController extends Controller
{
    public function show(ShowSelfPersonProfileRequest $request, PeopleDirectoryService $directoryService): Response
    {
        $personId = (int) $request->user()->person_id;
        $person = $directoryService->findPersonForDirectory($personId);

        return Inertia::render('Admin/MemberDirectory/SelfProfile', [
            'person' => DirectoryPersonPresenter::detail($person),
            'canManageRecords' => $request->user()->can(PeoplePermissions::UPDATE_MEMBER_RECORDS),
            'memberStatusOptions' => $directoryService->memberStatusOptions(),
        ]);
    }
}
