<?php

namespace Tests\Unit;

use App\Models\MemberProfile;
use App\Models\Person;
use PHPUnit\Framework\TestCase;

class PersonDisplayNameTest extends TestCase
{
    public function test_past_master_suffix_is_added_to_override_when_missing(): void
    {
        $person = new Person([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'display_name_override' => 'Brother John Doe',
        ]);
        $person->setRelation('memberProfile', new MemberProfile(['past_master' => true]));

        $this->assertSame('Brother John Doe, PM', $person->display_name);
    }

    public function test_past_master_suffix_is_added_when_no_override_exists(): void
    {
        $person = new Person([
            'first_name' => 'John',
            'middle_name' => 'Q',
            'last_name' => 'Doe',
        ]);
        $person->setRelation('memberProfile', new MemberProfile(['past_master' => true]));

        $this->assertSame('John Q Doe, PM', $person->display_name);
    }

    public function test_existing_pm_suffix_on_override_is_not_duplicated(): void
    {
        $person = new Person([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'display_name_override' => 'Brother John Doe, PM',
        ]);
        $person->setRelation('memberProfile', new MemberProfile(['past_master' => true]));

        $this->assertSame('Brother John Doe, PM', $person->display_name);
    }
}
