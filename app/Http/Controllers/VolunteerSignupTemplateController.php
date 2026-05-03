<?php

namespace App\Http\Controllers;

use App\Models\VolunteerSignupTemplate;
use App\Services\VolunteerSignupStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VolunteerSignupTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = VolunteerSignupTemplate::query()
            ->with('roles.slots')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (VolunteerSignupTemplate $template) => $this->toTemplateArray($template))
            ->values();

        return Inertia::render('Admin/VolunteerSignups/Templates', [
            'templates' => $templates,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request, VolunteerSignupStructureService $structureService): RedirectResponse
    {
        $data = $this->validateTemplate($request);

        $template = VolunteerSignupTemplate::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by_user_id' => $request->user()?->id,
        ]);

        $structureService->syncTemplateRoles($template, $data['roles']);

        return back()->with('success', 'Volunteer template created.');
    }

    /**
     * @throws Throwable
     */
    public function update(
        Request $request,
        VolunteerSignupTemplate $volunteerSignupTemplate,
        VolunteerSignupStructureService $structureService
    ): RedirectResponse {
        $data = $this->validateTemplate($request);

        $volunteerSignupTemplate->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ])->save();

        $structureService->syncTemplateRoles($volunteerSignupTemplate, $data['roles']);

        return back()->with('success', 'Volunteer template updated.');
    }

    public function destroy(VolunteerSignupTemplate $volunteerSignupTemplate): RedirectResponse
    {
        $volunteerSignupTemplate->delete();

        return back()->with('success', 'Volunteer template deleted.');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*.id' => ['nullable', 'integer'],
            'roles.*.title' => ['required', 'string', 'max:120'],
            'roles.*.description' => ['nullable', 'string'],
            'roles.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'roles.*.slots' => ['required', 'array', 'min:1'],
            'roles.*.slots.*.id' => ['nullable', 'integer'],
            'roles.*.slots.*.starts_at' => ['required', 'date_format:H:i'],
            'roles.*.slots.*.ends_at' => ['required', 'date_format:H:i'],
            'roles.*.slots.*.needed_count' => ['required', 'integer', 'min:1', 'max:500'],
            'roles.*.slots.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }

    private function toTemplateArray(VolunteerSignupTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'sort_order' => $template->sort_order,
            'is_active' => (bool) $template->is_active,
            'roles' => $template->roles
                ->sortBy(fn ($role) => [$role->sort_order, $role->id])
                ->values()
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'title' => $role->title,
                    'description' => $role->description,
                    'sort_order' => $role->sort_order,
                    'slots' => $role->slots
                        ->sortBy(fn ($slot) => [$slot->sort_order, $slot->id])
                        ->values()
                        ->map(fn ($slot) => [
                            'id' => $slot->id,
                            'starts_at' => substr((string) $slot->starts_at, 0, 5),
                            'ends_at' => substr((string) $slot->ends_at, 0, 5),
                            'needed_count' => $slot->needed_count,
                            'sort_order' => $slot->sort_order,
                        ])
                        ->all(),
                ])
                ->all(),
        ];
    }
}
