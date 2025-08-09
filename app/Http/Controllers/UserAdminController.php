<?php

namespace App\Http\Controllers;

use App\Helpers\Audit;
use App\Helpers\RoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Throwable;

class UserAdminController extends Controller
{
    private function requireConfirmation(Request $request): void
    {
        // If the user has recently confirmed via Fortify (session timestamp):
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 10800); // default 3 hours

        if ($confirmedAt && (time() - $confirmedAt) < $timeout) {
            return; // already confirmed, let it pass
        }

        // Otherwise, require the inline field (works with ConfirmsPassword that passes the password along)
        $request->validate([
            'confirm_password' => ['required', 'string', 'min:8'],
        ], [], ['confirm_password' => 'your password']);

        if (! Hash::check($request->input('confirm_password'), $request->user()->password)) {
            abort(403, 'Confirmation failed. Incorrect password.');
        }
    }

    private function assertAccess(Request $request): void
    {
        abort_unless(
            $request->user()->hasRole(RoleEnum::ADMIN->value) || $request->user()->hasRole(RoleEnum::SECRETARY->value),
            403
        );
    }

    private function abortIfSecretaryTargetingAdmin(User $actor, User $target): void
    {
        if ($actor->hasRole(RoleEnum::SECRETARY->value) && $target->hasRole(RoleEnum::ADMIN->value)) {
            abort(403, 'Secretaries cannot modify Administrators.');
        }
    }

    public function index(Request $request)
    {
        $this->assertAccess($request);

        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'roles' => $u->getRoleNames()->values(), // array of role names
                'isAdmin' => $u->hasRole(RoleEnum::ADMIN->value),
            ]);

        $allRoles = Role::query()->orderBy('name')->pluck('name');

        $sharedCan = $request->attributes
            ->get('inertia', [])['props']['can'] ?? [];

        // Merge with your extra flags
        $extraCan = [
            'isAdmin'     => $request->user()->hasRole(RoleEnum::ADMIN->value),
            'isSecretary' => $request->user()->hasRole(RoleEnum::SECRETARY->value),
        ];

        return Inertia::render('Admin/Users/Manage', [
            'users' => $users,
            'roles' => $allRoles,
        ]);
    }

    public function store(Request $request)
    {
        $this->assertAccess($request);
        $this->requireConfirmation($request);

        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['nullable','string','min:8'],
            'roles'    => ['array'],
            'roles.*'  => ['string'],
        ]);

        $user = new User([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        Audit::log(
            $request,
            action: 'user.create',
            subject: $user,
            changes: ['after' => [
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values(),
            ]],
            meta: ['set_password' => !empty($data['password'])]
        );

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $this->assertAccess($request);
        $this->abortIfSecretaryTargetingAdmin($request->user(), $user);
        $this->requireConfirmation($request);

        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email,'.$user->id],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $before = [
            'name' => $user->name,
            'email'=> $user->email,
            'roles'=> $user->getRoleNames()->values(),
        ];

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles']);
        }

        $after = [
            'name' => $user->name,
            'email'=> $user->email,
            'roles'=> $user->getRoleNames()->values(),
        ];

        Audit::log(
            $request,
            action: 'user.update',
            subject: $user,
            changes: compact('before','after')
        );

        return back()->with('success', 'User updated.');
    }

    public function setPassword(Request $request, User $user)
    {
        $this->assertAccess($request);
        $this->abortIfSecretaryTargetingAdmin($request->user(), $user);
        $this->requireConfirmation($request);

        $data = $request->validate([
            'password' => ['required','string','min:8','confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        Audit::log(
            $request,
            action: 'user.set_password',
            subject: $user,
            meta: ['length' => strlen($data['password'])]
        );

        return back()->with('success', 'Password updated.');
    }

    /**
     * @throws Throwable
     */
    public function bulkUpdate(Request $request)
    {
        $this->assertAccess($request);
        $this->requireConfirmation($request); // your existing helper

        $data = $request->validate([
            'items' => ['required','array','min:1'],
            'items.*.id'    => ['required','integer','exists:users,id'],
            'items.*.name'  => ['required','string','max:255'],
            'items.*.email' => ['required','email','max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (preg_match('/^items\.(\d+)\.email$/', $attribute, $m)) {
                        $index = (int) $m[1];
                        $userId = $request->input("items.$index.id");
                        $exists = \App\Models\User::where('email', $value)
                            ->where('id', '!=', $userId)
                            ->exists();
                        if ($exists) {
                            $fail('The email has already been taken.');
                        }
                    }
                },],
            'items.*.roles' => ['array'],
            'items.*.roles.*' => ['string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            foreach ($data['items'] as $row) {
                $user = User::findOrFail($row['id']);

                $this->abortIfSecretaryTargetingAdmin($request->user(), $user);

                $before = [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                ];

                $user->fill([
                    'name'  => $row['name'],
                    'email' => $row['email'],
                ])->save();

                if (array_key_exists('roles', $row)) {
                    $user->syncRoles($row['roles'] ?? []);
                }

                $after = [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                ];

                Audit::log($request, 'user.bulk_update.item', $user, compact('before','after'));
            }
        });

        return back()->with('success', 'Changes saved.');
    }
}
