<?php

namespace App\Services;

use App\Models\VolunteerSignupSheet;
use App\Models\VolunteerSignupSheetRole;
use App\Models\VolunteerSignupTemplate;
use App\Models\VolunteerSignupTemplateRole;
use Illuminate\Support\Facades\DB;
use Throwable;

class VolunteerSignupStructureService
{
    /**
     * @throws Throwable
     */
    public function syncTemplateRoles(VolunteerSignupTemplate $template, array $roles): void
    {
        DB::transaction(function () use ($template, $roles) {
            $template->load('roles.slots');
            $existingRoles = $template->roles->keyBy('id');
            $keepRoleIds = [];

            foreach ($roles as $roleIndex => $roleInput) {
                $role = $this->upsertTemplateRole($template, $existingRoles, $roleInput, $roleIndex);
                $keepRoleIds[] = $role->id;

                $this->syncTemplateSlots($role, (array) ($roleInput['slots'] ?? []));
            }

            if ($keepRoleIds) {
                VolunteerSignupTemplateRole::query()
                    ->where('volunteer_signup_template_id', $template->id)
                    ->whereNotIn('id', $keepRoleIds)
                    ->delete();
            } else {
                VolunteerSignupTemplateRole::query()
                    ->where('volunteer_signup_template_id', $template->id)
                    ->delete();
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function syncSheetRoles(VolunteerSignupSheet $sheet, array $roles): void
    {
        DB::transaction(function () use ($sheet, $roles) {
            $sheet->load('roles.slots');
            $existingRoles = $sheet->roles->keyBy('id');
            $keepRoleIds = [];

            foreach ($roles as $roleIndex => $roleInput) {
                $role = $this->upsertSheetRole($sheet, $existingRoles, $roleInput, $roleIndex);
                $keepRoleIds[] = $role->id;

                $this->syncSheetSlots($role, (array) ($roleInput['slots'] ?? []));
            }

            if ($keepRoleIds) {
                VolunteerSignupSheetRole::query()
                    ->where('volunteer_signup_sheet_id', $sheet->id)
                    ->whereNotIn('id', $keepRoleIds)
                    ->delete();
            } else {
                VolunteerSignupSheetRole::query()
                    ->where('volunteer_signup_sheet_id', $sheet->id)
                    ->delete();
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function applyTemplateToSheet(VolunteerSignupTemplate $template, VolunteerSignupSheet $sheet): void
    {
        $roles = $template->roles()
            ->with('slots')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($role) => [
                'title' => $role->title,
                'description' => $role->description,
                'sort_order' => $role->sort_order,
                'slots' => $role->slots
                    ->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])
                    ->values()
                    ->map(fn ($slot) => [
                        'starts_at' => $slot->starts_at,
                        'ends_at' => $slot->ends_at,
                        'needed_count' => $slot->needed_count,
                        'sort_order' => $slot->sort_order,
                    ])
                    ->all(),
            ])
            ->all();

        $this->syncSheetRoles($sheet, $roles);
    }

    protected function upsertTemplateRole(
        VolunteerSignupTemplate $template,
        $existingRoles,
        array $roleInput,
        int $roleIndex
    ): VolunteerSignupTemplateRole {
        $incomingId = isset($roleInput['id']) ? (int) $roleInput['id'] : null;
        $existing = $incomingId ? $existingRoles->get($incomingId) : null;

        if (!$existing) {
            $existing = new VolunteerSignupTemplateRole();
            $existing->volunteer_signup_template_id = $template->id;
        }

        $existing->fill([
            'title' => $roleInput['title'],
            'description' => $roleInput['description'] ?? null,
            'sort_order' => $roleInput['sort_order'] ?? $roleIndex,
        ]);
        $existing->save();

        return $existing;
    }

    protected function syncTemplateSlots(VolunteerSignupTemplateRole $role, array $slots): void
    {
        $role->loadMissing('slots');
        $existingSlots = $role->slots->keyBy('id');
        $keepSlotIds = [];

        foreach ($slots as $slotIndex => $slotInput) {
            $incomingId = isset($slotInput['id']) ? (int) $slotInput['id'] : null;
            $slot = $incomingId ? $existingSlots->get($incomingId) : null;

            if (!$slot) {
                $slot = $role->slots()->make();
            }

            $slot->fill([
                'starts_at' => $slotInput['starts_at'],
                'ends_at' => $slotInput['ends_at'],
                'needed_count' => $slotInput['needed_count'],
                'sort_order' => $slotInput['sort_order'] ?? $slotIndex,
            ]);
            $slot->save();
            $keepSlotIds[] = $slot->id;
        }

        if ($keepSlotIds) {
            $role->slots()->whereNotIn('id', $keepSlotIds)->delete();
        } else {
            $role->slots()->delete();
        }
    }

    protected function upsertSheetRole(
        VolunteerSignupSheet $sheet,
        $existingRoles,
        array $roleInput,
        int $roleIndex
    ): VolunteerSignupSheetRole {
        $incomingId = isset($roleInput['id']) ? (int) $roleInput['id'] : null;
        $existing = $incomingId ? $existingRoles->get($incomingId) : null;

        if (!$existing) {
            $existing = new VolunteerSignupSheetRole();
            $existing->volunteer_signup_sheet_id = $sheet->id;
        }

        $existing->fill([
            'title' => $roleInput['title'],
            'description' => $roleInput['description'] ?? null,
            'sort_order' => $roleInput['sort_order'] ?? $roleIndex,
        ]);
        $existing->save();

        return $existing;
    }

    protected function syncSheetSlots(VolunteerSignupSheetRole $role, array $slots): void
    {
        $role->loadMissing('slots');
        $existingSlots = $role->slots->keyBy('id');
        $keepSlotIds = [];

        foreach ($slots as $slotIndex => $slotInput) {
            $incomingId = isset($slotInput['id']) ? (int) $slotInput['id'] : null;
            $slot = $incomingId ? $existingSlots->get($incomingId) : null;

            if (!$slot) {
                $slot = $role->slots()->make();
            }

            $slot->fill([
                'starts_at' => $slotInput['starts_at'],
                'ends_at' => $slotInput['ends_at'],
                'needed_count' => $slotInput['needed_count'],
                'sort_order' => $slotInput['sort_order'] ?? $slotIndex,
            ]);
            $slot->save();
            $keepSlotIds[] = $slot->id;
        }

        if ($keepSlotIds) {
            $role->slots()->whereNotIn('id', $keepSlotIds)->delete();
        } else {
            $role->slots()->delete();
        }
    }
}
