<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Helpers\RoleEnum;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Read from env
        $email = config('site.admin_email');
        $name = config('site.admin_name', 'Administrator');
        $password = config('site.admin_password');
        $resetPasswordOnSeed = filter_var(config('site.admin_reset_password_on_seed', false), FILTER_VALIDATE_BOOL);

        if (!$email) {
            $this->command?->warn('ADMIN_EMAIL not set; skipping AdminUserSeeder.');
            return;
        }

        if (!$password) {
            $this->command?->warn('ADMIN_PASSWORD not set; skipping AdminUserSeeder.');
            return;
        }

        // If you're using Spatie, clearing cache before role assignment is a good habit
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create fresh admin user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'remember_token' => Str::random(10),
                // optionally:
                // 'email_verified_at' => now(),
            ]);
        } else {
            // Keep existing user, update name; only reset password if explicitly enabled
            $user->name = $name;

            if ($resetPasswordOnSeed) {
                $user->password = Hash::make($password);
            }

            $user->save();
        }

        // Assign Administrator role (adjust if your enum uses a different value/key)
        $user->syncRoles([RoleEnum::ADMIN->value]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
