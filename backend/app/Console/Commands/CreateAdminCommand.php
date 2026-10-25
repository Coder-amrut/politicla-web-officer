<?php

namespace App\Console\Commands;

use App\Enums\RegistrationSource;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create 
        {--name= : Full name of admin}
        {--email= : Email address of admin}
        {--phone= : Phone number of admin}
        {--password= : Password}';

    protected $description = 'Create or update an active ADMIN account in both users and admins tables.';

    public function handle(): int
    {
        $this->info('=== Create or Update Bootstrap ADMIN ===');
        $this->newLine();

        $fullName = $this->option('name') ?: $this->ask('Full name', 'Admin');
        $email = $this->option('email') ?: $this->ask('Email address');
        $phone = $this->option('phone') ?: $this->ask('Phone number');

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Password');
            $confirmation = $this->secret('Confirm password');
        } else {
            $confirmation = $password;
        }

        $email = trim(mb_strtolower((string) $email));
        $phone = trim((string) $phone);

        $validator = Validator::make(
            [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'full_name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:150'],
                'phone' => ['required', 'string', 'max:20'],
                'password' => ['required', 'confirmed', 'min:8'],
                'password_confirmation' => ['required'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $adminRole = Role::firstOrCreate(
            ['name' => RoleName::ADMIN->value],
            ['display_name' => 'Administrator', 'description' => 'System administrator']
        );

        $user = null;
        $admin = null;

        try {
            DB::transaction(function () use ($fullName, $email, $phone, $password, $adminRole, &$user, &$admin) {
                // 1. Synchronize in users table
                $user = User::where('email', $email)->orWhere('phone', $phone)->first();
                if ($user) {
                    $user->update([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => Hash::make($password),
                        'status' => UserStatus::ACTIVE->value,
                    ]);
                } else {
                    $user = User::create([
                        'uuid' => (string) Str::uuid(),
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => Hash::make($password),
                        'status' => UserStatus::ACTIVE->value,
                        'registration_source' => RegistrationSource::SYSTEM_BOOTSTRAP->value,
                    ]);
                }

                $user->roles()->syncWithoutDetaching([$adminRole->id]);

                // 2. Synchronize in dedicated admins table
                $admin = Admin::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $fullName,
                        'password' => Hash::make($password),
                    ]
                );
            });
        } catch (\Throwable $e) {
            $this->error('Failed to create/update admin account: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('ADMIN account created/updated successfully in both users and admins tables.');
        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', $user->id],
                ['Admin ID', $admin->id],
                ['UUID', $user->uuid],
                ['Name', $user->full_name],
                ['Email', $user->email],
                ['Phone', $user->phone],
                ['Status', $user->status],
                ['Roles', 'ADMIN'],
            ],
        );
        $this->comment('You can now log in with email: ' . $email . ' and the password you set.');

        return self::SUCCESS;
    }
}
