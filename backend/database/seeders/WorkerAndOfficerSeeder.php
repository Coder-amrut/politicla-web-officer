<?php

namespace Database\Seeders;

use App\Enums\RegistrationSource;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WorkerAndOfficerSeeder extends Seeder
{
    public function run(): void
    {
        $workerRole = Role::where('name', RoleName::WORKER->value)->firstOrFail();
        $officerRole = Role::where('name', RoleName::OFFICER->value)->firstOrFail();

        $defaultPassword = Hash::make('Password@123');

        $workers = [
            ['full_name' => 'Rahul Patil', 'phone' => '9822011221', 'email' => 'rahul.patil@field.in'],
            ['full_name' => 'Sachin Shinde', 'phone' => '9822011222', 'email' => 'sachin.shinde@field.in'],
            ['full_name' => 'Amit Deshmukh', 'phone' => '9822011223', 'email' => 'amit.deshmukh@field.in'],
            ['full_name' => 'Priya Kadam', 'phone' => '9822011224', 'email' => 'priya.kadam@field.in'],
            ['full_name' => 'Ganesh More', 'phone' => '9822011225', 'email' => 'ganesh.more@field.in'],
            ['full_name' => 'Sunil Jadhav', 'phone' => '9822011226', 'email' => 'sunil.jadhav@field.in'],
            ['full_name' => 'Pooja Sawant', 'phone' => '9822011227', 'email' => 'pooja.sawant@field.in'],
        ];

        $officers = [
            ['full_name' => 'Vikram Chavan', 'phone' => '9822011231', 'email' => 'vikram.chavan@field.in'],
            ['full_name' => 'Sneha Bhosale', 'phone' => '9822011232', 'email' => 'sneha.bhosale@field.in'],
            ['full_name' => 'Mahesh Jagtap', 'phone' => '9822011233', 'email' => 'mahesh.jagtap@field.in'],
            ['full_name' => 'Anand Kulkarni', 'phone' => '9822011234', 'email' => 'anand.kulkarni@field.in'],
        ];

        foreach ($workers as $data) {
            $user = User::firstOrCreate(
                ['phone' => $data['phone']],
                [
                    'uuid'                => (string) Str::uuid(),
                    'full_name'           => $data['full_name'],
                    'email'               => $data['email'],
                    'password_hash'       => $defaultPassword,
                    'status'              => UserStatus::ACTIVE->value,
                    'registration_source' => RegistrationSource::ADMIN_CREATED->value,
                    'approved_at'         => now(),
                ]
            );
            $user->roles()->syncWithoutDetaching([$workerRole->id]);
        }

        foreach ($officers as $data) {
            $user = User::firstOrCreate(
                ['phone' => $data['phone']],
                [
                    'uuid'                => (string) Str::uuid(),
                    'full_name'           => $data['full_name'],
                    'email'               => $data['email'],
                    'password_hash'       => $defaultPassword,
                    'status'              => UserStatus::ACTIVE->value,
                    'registration_source' => RegistrationSource::ADMIN_CREATED->value,
                    'approved_at'         => now(),
                ]
            );
            $user->roles()->syncWithoutDetaching([$officerRole->id]);
        }
    }
}
