<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAttendanceSeeder extends Seeder
{
    /**
     * Seed initial user attendances with addresses and geofence locations.
     */
    public function run(): void
    {
        // 1. Ensure Attendance Setting is present
        DB::table('attendance_settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_location_required'   => false, // Flexible by default (Admin can turn ON anytime)
                'allow_anywhere'          => true,
                'location_name'           => 'सांगली मध्यवर्ती निवडणूक नियंत्रण कक्ष (Sangli Central HQ)',
                'address'                 => 'स्टेशन रोड, विश्रामबाग जवळ, सांगली, महाराष्ट्र ४१६४१६',
                'latitude'                => 16.8524000,
                'longitude'               => 74.5815000,
                'radius_meters'           => 500,
                'enforce_strict_geofence' => true,
                'updated_by_name'         => 'Admin Command HQ',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]
        );

        $sampleLocations = [
            [
                'address' => 'स्टेशन चौक, सांगली मध्यवर्ती कार्यालय, सांगली ४१६४१६',
                'lat'     => 16.8524,
                'lng'     => 74.5815,
                'dist'    => 45,
                'comp'    => 'AT_REQUIRED_LOCATION',
            ],
            [
                'address' => 'राजवाडा चौक, हरभट रोड, सांगली शहर ४१६४१६',
                'lat'     => 16.8550,
                'lng'     => 74.5780,
                'dist'    => 120,
                'comp'    => 'AT_REQUIRED_LOCATION',
            ],
            [
                'address' => 'विश्रामबाग १०० फूट रोड, सांगली ४१६४१५',
                'lat'     => 16.8480,
                'lng'     => 74.5920,
                'dist'    => 280,
                'comp'    => 'AT_REQUIRED_LOCATION',
            ],
            [
                'address' => 'गणपती पेठ, मारुती चौक, सांगली ४१६४१६',
                'lat'     => 16.8590,
                'lng'     => 74.5710,
                'dist'    => 310,
                'comp'    => 'AT_REQUIRED_LOCATION',
            ],
            [
                'address' => 'कुपवाड एमआयडीसी क्षेत्र, कुपवाड, सांगली ४१६४३६',
                'lat'     => 16.8710,
                'lng'     => 74.6200,
                'dist'    => 450,
                'comp'    => 'AT_REQUIRED_LOCATION',
            ],
            [
                'address' => 'मिरज रेल्वे जंक्शन परिसर, मिरज, सांगली ४१६४१०',
                'lat'     => 16.8270,
                'lng'     => 74.6430,
                'dist'    => 840,
                'comp'    => 'ANYWHERE_ALLOWED',
            ],
            [
                'address' => 'तासगाव बस स्थानक चौक, तासगाव, सांगली ४१६३१२',
                'lat'     => 17.0340,
                'lng'     => 74.6020,
                'dist'    => 1250,
                'comp'    => 'ANYWHERE_ALLOWED',
            ],
            [
                'address' => 'इस्लामपूर कचेरी रोड, वाळवा, सांगली ४१५४०९',
                'lat'     => 17.0510,
                'lng'     => 74.2640,
                'dist'    => 1890,
                'comp'    => 'ANYWHERE_ALLOWED',
            ],
            [
                'address' => 'शिराळा पेठ परिसर, शिराळा, सांगली ४१५४०८',
                'lat'     => 16.9800,
                'lng'     => 74.1300,
                'dist'    => 2200,
                'comp'    => 'ANYWHERE_ALLOWED',
            ],
            [
                'address' => 'जत नगरपरिषद परिसर, जत, सांगली ४१६४०४',
                'lat'     => 17.0450,
                'lng'     => 75.1670,
                'dist'    => 3500,
                'comp'    => 'ANYWHERE_ALLOWED',
            ],
        ];

        $users = DB::table('users')->where('status', 'ACTIVE')->get();
        $dates = [];
        for ($d = 0; $d < 14; $d++) {
            $dates[] = now()->subDays($d)->toDateString();
        }

        foreach ($dates as $date) {
            $i = 0;
            foreach ($users as $u) {
                // Skip some users for realistic absence rate
                if ($i % 5 === 4) {
                    $i++;
                    continue;
                }

                $sample = $sampleLocations[$i % count($sampleLocations)];
                $role = DB::table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('user_roles.user_id', $u->id)
                    ->value('roles.name') ?? 'WORKER';

                $inHour = 8 + ($i % 3);
                $inMin = sprintf('%02d', ($i * 11) % 60);
                $inTime = sprintf('%02d:%s AM', $inHour, $inMin);

                $isOut = ($i % 3 !== 0) || ($date !== now()->toDateString());
                $outTime = $isOut ? sprintf('05:%02d PM', ($i * 13) % 60) : '-';
                $status = $isOut ? 'Completed' : 'Present';

                DB::table('user_attendances')->updateOrInsert(
                    [
                        'user_id' => $u->id,
                        'date'    => $date,
                    ],
                    [
                        'user_name'         => $u->full_name,
                        'user_role'         => $role,
                        'punch_in_time'     => $inTime,
                        'punch_out_time'    => $outTime,
                        'status'            => $status,
                        'latitude'          => $sample['lat'],
                        'longitude'         => $sample['lng'],
                        'address'           => $sample['address'],
                        'location_name'     => 'सांगली मध्यवर्ती कार्यालय',
                        'distance_meters'   => $sample['dist'],
                        'compliance_status' => $sample['comp'],
                        'device_info'       => 'Android Mobile GPS / Biometric Camera',
                        'notes'             => 'नियमित दैनंदिन हजेरी नोंदणी',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]
                );
                $i++;
            }
        }
    }
}
