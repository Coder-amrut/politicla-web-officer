<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seed exactly 10 simple permissions representing the core platform panels and features.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'admin_panel',
                'description' => 'Full access to Admin Command HQ, master settings, and platform control',
            ],
            [
                'name' => 'political_command',
                'description' => 'Access to Political Command HQ, Executive Dashboard, and Neta Desk',
            ],
            [
                'name' => 'user_management',
                'description' => 'Manage user accounts, approve registrations, and assign roles',
            ],
            [
                'name' => 'project_management',
                'description' => 'Create, configure, and monitor political election projects',
            ],
            [
                'name' => 'geography_booths',
                'description' => 'Manage constituencies, talukas, villages, and polling booths',
            ],
            [
                'name' => 'voter_surveys',
                'description' => 'Conduct household surveys and record ground voter data',
            ],
            [
                'name' => 'citizen_crm',
                'description' => 'Manage 360° citizen voter profiles, contacts, and grievances',
            ],
            [
                'name' => 'meetings_tours',
                'description' => 'Schedule and manage political meetings, rallies, and leader field tours',
            ],
            [
                'name' => 'strategic_reports',
                'description' => 'Access ground pulse reports, voter trend analytics, and data exports',
            ],
            [
                'name' => 'ai_chatbot',
                'description' => 'Access AI Political Campaign Assistant and speech guidance desk',
            ],
        ];

        // Safely reset existing permissions
        Schema::disableForeignKeyConstraints();
        DB::table('role_permissions')->truncate();
        if (Schema::hasTable('user_permissions')) {
            DB::table('user_permissions')->truncate();
        }
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($permissions as $p) {
            Permission::create([
                'name' => $p['name'],
                'description' => $p['description'],
            ]);
        }

        // Assign default permissions to existing roles
        $adminRole = Role::where('name', 'ADMIN')->first();
        $officerRole = Role::where('name', 'OFFICER')->first();
        $workerRole = Role::where('name', 'WORKER')->first();

        $allPermIds = Permission::pluck('id')->toArray();

        // Admin has all 10 permissions
        if ($adminRole && !empty($allPermIds)) {
            $adminRole->permissions()->sync($allPermIds);
        }

        // Officer has Political Command, Projects, Geography, Surveys, CRM, Tours, Reports, AI Chatbot
        if ($officerRole) {
            $officerPermNames = [
                'political_command',
                'project_management',
                'geography_booths',
                'voter_surveys',
                'citizen_crm',
                'meetings_tours',
                'strategic_reports',
                'ai_chatbot',
            ];
            $officerPermIds = Permission::whereIn('name', $officerPermNames)->pluck('id')->toArray();
            $officerRole->permissions()->sync($officerPermIds);
        }

        // Worker has Field Surveys, Citizen CRM, Geography Booths, Meetings
        if ($workerRole) {
            $workerPermNames = [
                'voter_surveys',
                'citizen_crm',
                'geography_booths',
                'meetings_tours',
            ];
            $workerPermIds = Permission::whereIn('name', $workerPermNames)->pluck('id')->toArray();
            $workerRole->permissions()->sync($workerPermIds);
        }
    }
}
