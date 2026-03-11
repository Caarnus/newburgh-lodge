<?php

namespace App\Http\Middleware;

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $guard = 'web';

        $adminRole = RoleEnum::ADMIN;
        if ($adminRole instanceof \BackedEnum) {
            $adminRole = $adminRole->value;
        }

        $secretaryRole = RoleEnum::SECRETARY;
        if ($secretaryRole instanceof \BackedEnum) {
            $secretaryRole = $secretaryRole->value;
        }

        $peopleCan = [
            'members' => $user?->hasPermissionTo(PeoplePermissions::VIEW_MEMBER_DIRECTORY, $guard) ?? false,
            'widows' => $user?->hasPermissionTo(PeoplePermissions::VIEW_WIDOW_DIRECTORY, $guard) ?? false,
            'orphans' => $user?->hasPermissionTo(PeoplePermissions::VIEW_ORPHAN_DIRECTORY, $guard) ?? false,
            'details' => $user?->hasPermissionTo(PeoplePermissions::VIEW_MEMBER_DETAILS, $guard) ?? false,
            'updateRecords' => $user?->hasPermissionTo(PeoplePermissions::UPDATE_MEMBER_RECORDS, $guard) ?? false,
            'importRoster' => $user?->hasPermissionTo(PeoplePermissions::IMPORT_MEMBER_ROSTER, $guard) ?? false,
            'mergeRecords' => $user?->hasPermissionTo(PeoplePermissions::MERGE_PEOPLE_RECORDS, $guard) ?? false,
            'logContacts' => $user?->hasPermissionTo(PeoplePermissions::LOG_CARE_CONTACTS, $guard) ?? false,
            'editContacts' => $user?->hasPermissionTo(PeoplePermissions::EDIT_CARE_CONTACTS, $guard) ?? false,
            'exportDirectory' => $user?->hasPermissionTo(PeoplePermissions::EXPORT_MEMBER_DIRECTORY, $guard) ?? false,
            'viewOwnProfile' => $user?->hasPermissionTo(PeoplePermissions::VIEW_OWN_PERSON_PROFILE, $guard) ?? false,
            'updateOwnProfile' => $user?->hasPermissionTo(PeoplePermissions::UPDATE_OWN_PERSON_PROFILE, $guard) ?? false,
        ];

        $peopleCan['directory'] = $peopleCan['members'] || $peopleCan['widows'] || $peopleCan['orphans'];
        $peopleCan['any'] = $peopleCan['directory']
            || $peopleCan['details']
            || $peopleCan['updateRecords']
            || $peopleCan['importRoster']
            || $peopleCan['mergeRecords']
            || $peopleCan['logContacts']
            || $peopleCan['editContacts']
            || $peopleCan['exportDirectory'];

        return array_merge(parent::share($request), [
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],

            'site' => [
                'newsletterLabel' => config('site.newsletter_label'),
                'orgName' => config('site.org_name'),
            ],

            'can' => [
                'newsletter' => [
                    'create' => $user?->hasPermissionTo('create newsletter', $guard) ?? false,
                    'update' => $user?->hasPermissionTo('update newsletter', $guard) ?? false,
                    'delete' => $user?->hasPermissionTo('delete newsletter', $guard) ?? false,
                ],
                'admin' => [
                    'users' => $user?->hasAnyRole([$adminRole, $secretaryRole]) ?? false,
                ],
                'manage' => [
                    'content' => $user?->hasPermissionTo('manage-content', $guard) ?? false,
                    'gallery' => $user?->hasPermissionTo('manage-gallery', $guard) ?? false,
                    'scholarships' => $user?->hasPermissionTo('review scholarship applications', $guard) ?? false,
                    'people' => $peopleCan,
                ],
                'isAdmin' => $user?->hasRole($adminRole) ?? false,
                'isSecretary' => $user?->hasRole($secretaryRole) ?? false,
            ],
        ]);
    }
}
