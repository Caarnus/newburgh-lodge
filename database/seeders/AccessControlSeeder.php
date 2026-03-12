<?php

namespace Database\Seeders;

use App\Helpers\People\PeoplePermissions;
use App\Helpers\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view newsletter',
            'create newsletter',
            'update newsletter',
            'delete newsletter',
            'view event',
            'create event',
            'update event',
            'delete event',
            'manage-content',
            'manage-gallery',
            'view member photos',
            'review scholarship applications',
            ...PeoplePermissions::all(),
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $roles = [];
        foreach (RoleEnum::cases() as $case) {
            $roles[$case->value] = Role::firstOrCreate([
                'name' => $case->value,
                'guard_name' => $guard,
            ]);
        }

        $roles['Care Committee'] = Role::firstOrCreate([
            'name' => 'Care Committee',
            'guard_name' => $guard,
        ]);

        $roles[RoleEnum::MEMBER->value]?->syncPermissions([
            'view newsletter',
            'view event',
            'view member photos',
            PeoplePermissions::VIEW_MEMBER_DIRECTORY,
            PeoplePermissions::VIEW_WIDOW_DIRECTORY,
            PeoplePermissions::VIEW_ORPHAN_DIRECTORY,
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::VIEW_OWN_PERSON_PROFILE,
            PeoplePermissions::UPDATE_OWN_PERSON_PROFILE,
        ]);

        $roles[RoleEnum::OFFICER->value]?->syncPermissions([
            'view newsletter',
            'create newsletter',
            'update newsletter',
            'view event',
            'create event',
            'update event',
            'delete event',
            'manage-content',
            'manage-gallery',
            'view member photos',
            PeoplePermissions::VIEW_MEMBER_DIRECTORY,
            PeoplePermissions::VIEW_WIDOW_DIRECTORY,
            PeoplePermissions::VIEW_ORPHAN_DIRECTORY,
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::LOG_CARE_CONTACTS,
            PeoplePermissions::EDIT_CARE_CONTACTS,
        ]);

        $roles[RoleEnum::SECRETARY->value]?->syncPermissions([
            'view newsletter',
            'create newsletter',
            'update newsletter',
            'delete newsletter',
            'view event',
            'create event',
            'update event',
            'delete event',
            'manage-content',
            'manage-gallery',
            'view member photos',
            PeoplePermissions::VIEW_MEMBER_DIRECTORY,
            PeoplePermissions::IMPORT_MEMBER_ROSTER,
            PeoplePermissions::MERGE_PEOPLE_RECORDS,
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::UPDATE_MEMBER_RECORDS,
            PeoplePermissions::VIEW_WIDOW_DIRECTORY,
            PeoplePermissions::VIEW_ORPHAN_DIRECTORY,
            PeoplePermissions::LOG_CARE_CONTACTS,
            PeoplePermissions::EDIT_CARE_CONTACTS,
            PeoplePermissions::EXPORT_MEMBER_DIRECTORY,
        ]);

        $roles['Care Committee']?->syncPermissions([
            PeoplePermissions::VIEW_WIDOW_DIRECTORY,
            PeoplePermissions::VIEW_ORPHAN_DIRECTORY,
            PeoplePermissions::VIEW_MEMBER_DETAILS,
            PeoplePermissions::LOG_CARE_CONTACTS,
            PeoplePermissions::EDIT_CARE_CONTACTS,
        ]);

        $roles[RoleEnum::ADMIN->value]?->syncPermissions(
            Permission::query()
                ->where('guard_name', $guard)
                ->pluck('name')
                ->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
