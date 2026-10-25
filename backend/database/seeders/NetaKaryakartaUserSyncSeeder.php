<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class NetaKaryakartaUserSyncSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['WORKER', 'OFFICER']);
            })
            ->get();

        DB::table('neta_karyakartas')->truncate();

        $defaultAreas = [
            3 => ['area_mr' => 'माधवनगर (बूथ १ व २)', 'area_en' => 'Madhavnagar (Booth 1 & 2)', 'surveys' => 584, 'today' => 24, 'rating' => 4.90, 'coverage' => 92, 'badges' => ['Top Performer', 'Booth Master']],
            4 => ['area_mr' => 'वसंत कॉलनी (बूथ ३)', 'area_en' => 'Vasant Colony (Booth 3)', 'surveys' => 512, 'today' => 19, 'rating' => 4.80, 'coverage' => 88, 'badges' => ['Consistent Contributor']],
            5 => ['area_mr' => 'विश्रामबाग (बूथ ८)', 'area_en' => 'Vishrambag (Booth 8)', 'surveys' => 476, 'today' => 26, 'rating' => 4.90, 'coverage' => 85, 'badges' => ['Youth Mobilizer']],
            6 => ['area_mr' => 'गणपती पेठ व गावभाग', 'area_en' => 'Ganpati Peth & Gavbhag', 'surveys' => 615, 'today' => 31, 'rating' => 5.00, 'coverage' => 95, 'badges' => ['Star Organizer', 'Ladki Bahin Lead']],
            7 => ['area_mr' => 'बुधगाव (बूथ ७)', 'area_en' => 'Budhgaon (Booth 7)', 'surveys' => 420, 'today' => 15, 'rating' => 4.60, 'coverage' => 79, 'badges' => ['Rural Reach']],
            8 => ['area_mr' => 'गणपती पेठ (बूथ ६)', 'area_en' => 'Ganpati Peth (Booth 6)', 'surveys' => 390, 'today' => 12, 'rating' => 4.50, 'coverage' => 83, 'badges' => ['Business Outreach']],
            9 => ['area_mr' => 'कुपवाड रोड (बूथ ५)', 'area_en' => 'Kupwad Road (Booth 5)', 'surveys' => 285, 'today' => 8, 'rating' => 3.90, 'coverage' => 48, 'badges' => ['Needs Training']],
            10 => ['area_mr' => 'सांगली शहर प्रभाग १-४', 'area_en' => 'Sangli City Ward 1-4', 'surveys' => 450, 'today' => 18, 'rating' => 4.75, 'coverage' => 82, 'badges' => ['Senior Inspector']],
            11 => ['area_mr' => 'मिरज ग्रामीण विभाग', 'area_en' => 'Miraj Rural Division', 'surveys' => 380, 'today' => 14, 'rating' => 4.65, 'coverage' => 78, 'badges' => ['Field Supervisor']],
            12 => ['area_mr' => 'सांगली पश्चिम विभाग', 'area_en' => 'Sangli West Division', 'surveys' => 340, 'today' => 12, 'rating' => 4.55, 'coverage' => 75, 'badges' => ['Operations Lead']],
            13 => ['area_mr' => 'सांगली मध्यवर्ती नियंत्रण कक्ष', 'area_en' => 'Sangli Central Command Cell', 'surveys' => 310, 'today' => 10, 'rating' => 4.50, 'coverage' => 72, 'badges' => ['Data Coordinator']],
            14 => ['area_mr' => 'संजयनगर (बूथ १०)', 'area_en' => 'Sanjaynagar (Booth 10)', 'surveys' => 340, 'today' => 14, 'rating' => 4.40, 'coverage' => 66, 'badges' => ['Community Connect']],
        ];

        foreach ($users as $u) {
            $role = $u->roles->first();
            $roleName = $role ? $role->name : 'WORKER';
            $roleDisplay = $role ? $role->display_name : 'Field Worker';
            $roleMr = $roleName === 'OFFICER' ? 'कक्ष अधिकारी / समन्वयक' : 'फील्ड कार्यकर्ता / बूथ इनचार्ज';

            $meta = $defaultAreas[$u->id] ?? [
                'area_mr' => 'सांगली कार्यक्षेत्र',
                'area_en' => 'Sangli Constituency',
                'surveys' => 120,
                'today' => 6,
                'rating' => 4.50,
                'coverage' => 65,
                'badges' => ['Ground Worker'],
            ];

            DB::table('neta_karyakartas')->insert([
                'user_id' => $u->id,
                'name' => $u->full_name,
                'phone' => $u->phone,
                'role_mr' => $roleMr,
                'role_en' => $roleDisplay,
                'assigned_area_mr' => $meta['area_mr'],
                'assigned_area_en' => $meta['area_en'],
                'total_surveys' => $meta['surveys'],
                'today_surveys' => $meta['today'],
                'rating' => $meta['rating'],
                'status' => strtolower($u->status) === 'active' ? 'active' : 'offline',
                'coverage_pct' => $meta['coverage'],
                'active_since' => '2023-01-01',
                'badges' => json_encode($meta['badges']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
