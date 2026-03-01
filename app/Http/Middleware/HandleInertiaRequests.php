<?php

namespace App\Http\Middleware;

use App\Helpers\RoleEnum;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $guard = 'web';

        // If RoleEnum is a PHP backed enum, pass ->value; otherwise it's already a string.
        $adminRole = RoleEnum::ADMIN;
        if ($adminRole instanceof \BackedEnum) {
            $adminRole = $adminRole->value;
        }

        $secretaryRole = RoleEnum::SECRETARY;
        if ($secretaryRole instanceof \BackedEnum) {
            $secretaryRole = $secretaryRole->value;
        }

        return array_merge(parent::share($request), [
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
                    // Prefer a permission if you have one; otherwise check roles.
                    'users' => $user?->hasAnyRole([$adminRole, $secretaryRole]) ?? false,
                ],
                'manage' => [
                    'content' => $user?->hasPermissionTo('manage-content', $guard) ?? false,
                    'gallery' => $user?->hasPermissionTo('manage-gallery', $guard) ?? false,
                    'scholarships' => $user?->hasPermissionTo('review scholarship applications', $guard) ?? false,
                ],
                'isAdmin'     => $user?->hasRole($adminRole) ?? false,
                'isSecretary' => $user?->hasRole($secretaryRole) ?? false,
            ],
        ]);
    }
}
