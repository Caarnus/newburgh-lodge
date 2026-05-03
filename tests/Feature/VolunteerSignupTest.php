<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\OrgEvent;
use App\Models\Person;
use App\Models\VolunteerSignupSheet;
use App\Models\VolunteerSignupSheetRole;
use App\Models\VolunteerSignupSheetSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VolunteerSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_signup_creates_user_registrant_and_assignment(): void
    {
        $this->seedVolunteerRoles();

        $event = OrgEvent::create([
            'title' => 'Breakfast Fundraiser',
            'start' => now()->addDays(20),
            'end' => now()->addDays(20)->addHours(4),
            'location' => 'Lodge Dining Room',
            'is_public' => true,
            'timezone' => 'America/Chicago',
        ]);

        $sheet = VolunteerSignupSheet::create([
            'org_event_id' => $event->id,
            'is_enabled' => true,
            'slug' => 'breakfast-volunteers',
            'remind_week_before' => true,
            'remind_day_before' => true,
        ]);

        $role = VolunteerSignupSheetRole::create([
            'volunteer_signup_sheet_id' => $sheet->id,
            'title' => 'Dining Room',
            'sort_order' => 1,
        ]);

        $slot = VolunteerSignupSheetSlot::create([
            'volunteer_signup_sheet_role_id' => $role->id,
            'starts_at' => '07:00',
            'ends_at' => '10:00',
            'needed_count' => 3,
            'sort_order' => 1,
        ]);

        $response = $this->post(route('public.volunteer-signups.store', $sheet->slug), [
            'name' => 'John Volunteer',
            'email' => 'john@example.com',
            'slot_ids' => [$slot->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Volunteer',
        ]);

        $this->assertDatabaseHas('volunteer_signup_registrants', [
            'volunteer_signup_sheet_id' => $sheet->id,
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('volunteer_signup_assignments', [
            'volunteer_signup_sheet_slot_id' => $slot->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('volunteer_signup_reminders', 2);
    }

    public function test_public_signup_links_to_member_roster_person_by_email(): void
    {
        $this->seedVolunteerRoles();

        $person = Person::create([
            'first_name' => 'Michael',
            'last_name' => 'Mason',
            'email' => 'michael@example.com',
        ]);

        MemberProfile::create([
            'person_id' => $person->id,
            'member_number' => 'M1234',
            'status' => 'active',
            'can_auto_match_registration' => true,
        ]);

        $event = OrgEvent::create([
            'title' => 'Degree Night',
            'start' => now()->addDays(14),
            'end' => now()->addDays(14)->addHours(2),
            'location' => 'Lodge Room',
            'is_public' => true,
            'timezone' => 'America/Chicago',
        ]);

        $sheet = VolunteerSignupSheet::create([
            'org_event_id' => $event->id,
            'is_enabled' => true,
            'slug' => 'degree-night-volunteers',
        ]);

        $role = VolunteerSignupSheetRole::create([
            'volunteer_signup_sheet_id' => $sheet->id,
            'title' => 'Preparation',
            'sort_order' => 1,
        ]);

        $slot = VolunteerSignupSheetSlot::create([
            'volunteer_signup_sheet_role_id' => $role->id,
            'starts_at' => '18:00',
            'ends_at' => '20:00',
            'needed_count' => 2,
            'sort_order' => 1,
        ]);

        $this->post(route('public.volunteer-signups.store', $sheet->slug), [
            'name' => 'Michael Mason',
            'email' => 'michael@example.com',
            'slot_ids' => [$slot->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('volunteer_signup_registrants', [
            'volunteer_signup_sheet_id' => $sheet->id,
            'email' => 'michael@example.com',
            'person_id' => $person->id,
        ]);
    }

    private function seedVolunteerRoles(): void
    {
        Role::findOrCreate('User', 'web');
        Role::findOrCreate('Member', 'web');
    }
}
