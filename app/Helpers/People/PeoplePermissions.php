<?php

namespace App\Helpers\People;

final class PeoplePermissions
{
    public const VIEW_MEMBER_DIRECTORY = 'view member directory';
    public const IMPORT_MEMBER_ROSTER = 'import member roster';
    public const MERGE_PEOPLE_RECORDS = 'merge people records';
    public const VIEW_MEMBER_DETAILS = 'view member details';
    public const UPDATE_MEMBER_RECORDS = 'update member records';
    public const VIEW_WIDOW_DIRECTORY = 'view widow directory';
    public const VIEW_ORPHAN_DIRECTORY = 'view orphan directory';
    public const LOG_CARE_CONTACTS = 'log care contacts';
    public const EDIT_CARE_CONTACTS = 'edit care contacts';
    public const VIEW_OWN_PERSON_PROFILE = 'view own person profile';
    public const UPDATE_OWN_PERSON_PROFILE = 'update own person profile';
    public const EXPORT_MEMBER_DIRECTORY = 'export member directory';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::VIEW_MEMBER_DIRECTORY,
            self::IMPORT_MEMBER_ROSTER,
            self::MERGE_PEOPLE_RECORDS,
            self::VIEW_MEMBER_DETAILS,
            self::UPDATE_MEMBER_RECORDS,
            self::VIEW_WIDOW_DIRECTORY,
            self::VIEW_ORPHAN_DIRECTORY,
            self::LOG_CARE_CONTACTS,
            self::EDIT_CARE_CONTACTS,
            self::VIEW_OWN_PERSON_PROFILE,
            self::UPDATE_OWN_PERSON_PROFILE,
            self::EXPORT_MEMBER_DIRECTORY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function directoryPermissions(): array
    {
        return [
            self::VIEW_MEMBER_DIRECTORY,
            self::VIEW_WIDOW_DIRECTORY,
            self::VIEW_ORPHAN_DIRECTORY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function carePermissions(): array
    {
        return [
            self::LOG_CARE_CONTACTS,
            self::EDIT_CARE_CONTACTS,
        ];
    }
}
