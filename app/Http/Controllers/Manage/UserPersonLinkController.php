<?php

namespace App\Http\Controllers\Manage;

use App\Enums\UserPersonLinkAction;
use App\Helpers\People\PeoplePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\LinkUserToPersonRequest;
use App\Http\Requests\People\SearchPeopleForUserLinkRequest;
use App\Http\Requests\People\UnlinkUserFromPersonRequest;
use App\Models\Person;
use App\Models\User;
use App\Services\People\RegistrationMemberService;
use App\Services\People\UserPersonLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;

class UserPersonLinkController extends Controller
{
    public function show(User $user, RegistrationMemberService $matcher): JsonResponse
    {
        abort_unless(request()->user()?->can(PeoplePermissions::UPDATE_MEMBER_RECORDS), 403);

        return response()->json($matcher->buildStatusPayload($user));
    }

    public function searchPeople(SearchPeopleForUserLinkRequest $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $lowerTerm = Str::lower($term);

        $people = Person::query()
            ->with('memberProfile')
            ->where(function ($query) use ($term, $lowerTerm) {
                $query->whereRaw('LOWER(first_name) like ?', ["%{$lowerTerm}%"])
                    ->orWhereRaw('LOWER(last_name) like ?', ["%{$lowerTerm}%"])
                    ->orWhereRaw('LOWER(preferred_name) like ?', ["%{$lowerTerm}%"])
                    ->orWhereRaw('LOWER(email) like ?', ["%{$lowerTerm}%"])
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('memberProfile', function ($memberQuery) use ($term) {
                        $memberQuery->where('member_number', 'like', "%{$term}%");
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        $linkedUserIds = User::query()
            ->whereIn('person_id', $people->pluck('id'))
            ->pluck('id', 'person_id');

        return response()->json(
            $people->map(fn (Person $person) => [
                'id' => $person->id,
                'display_name' => $person->display_name,
                'email' => $person->email,
                'phone' => $person->phone,
                'is_deceased' => $person->is_deceased,
                'death_date' => optional($person->death_date)?->toDateString(),
                'linked_user_id' => $linkedUserIds[$person->id] ?? null,
                'member_profile' => $person->memberProfile ? [
                    'member_number' => $person->memberProfile->member_number,
                    'status' => $person->memberProfile->status,
                ] : null,
            ])->values()
        );
    }

    /**
     * @throws Throwable
     */
    public function link(
        LinkUserToPersonRequest $request,
        User $user,
        UserPersonLinkService $linkService,
        RegistrationMemberService $matcher,
    ): JsonResponse {
        $person = Person::query()
            ->with('memberProfile')
            ->findOrFail($request->integer('person_id'));

        $action = $user->person_id
            ? UserPersonLinkAction::ManualRelinked
            : UserPersonLinkAction::ManualLinked;

        $linkService->link(
            user: $user,
            person: $person,
            creatorId: $request->user()?->id,
            action: $action,
            matchStrategy: 'manual',
            notes: $request->string('notes')->toString() ?: null,
        );

        return response()->json($matcher->buildStatusPayload($user->fresh()));
    }

    /**
     * @throws Throwable
     */
    public function unlink(
        UnlinkUserFromPersonRequest $request,
        User $user,
        UserPersonLinkService $linkService,
        RegistrationMemberService $matcher,
    ): JsonResponse {
        $linkService->unlink(
            user: $user,
            creatorId: $request->user()?->id,
            notes: $request->string('notes')->toString() ?: null,
            removeMemberRole: $request->boolean('remove_member_role'),
        );

        return response()->json($matcher->buildStatusPayload($user->fresh()));
    }
}
