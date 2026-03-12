<?php

namespace App\Http\Controllers\Manage;

use App\Helpers\People\DirectoryPersonPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\ShowPersonDirectoryRequest;
use App\Models\Person;
use App\Services\People\Directory\PeopleDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

class PersonDirectoryController extends Controller
{
    public function show(ShowPersonDirectoryRequest $request, Person $person, PeopleDirectoryService $directoryService): Response
    {
        $person = $directoryService->findPersonForDirectory($person->id);

        return Inertia::render('Admin/MemberDirectory/Show', [
            'person' => DirectoryPersonPresenter::detail($person),
            'fromSection' => $request->string('from')->toString() ?: null,
        ]);
    }
}
