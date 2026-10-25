<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WorkerDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Worker Profiles for workers
        $workerUsers = DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'WORKER')
            ->select('users.id', 'users.full_name', 'users.phone', 'users.email')
            ->get();

        foreach ($workerUsers as $w) {
            DB::table('worker_profiles')->updateOrInsert(
                ['user_id' => $w->id],
                [
                    'responsibility' => 'बूथ व मतदार संपर्क प्रमुख',
                    'employee_code'  => 'WRK-' . str_pad($w->id, 4, '0', STR_PAD_LEFT),
                    'joining_date'   => '2023-01-15',
                    'dob'            => '1992-05-18',
                    'address'        => 'घर क्र. ४५, गणपती पेठ, मारुती मंदिर जवळ, सांगली - ४१६४१६',
                    'assigned_ward'  => 'प्रभाग क्र. ४ - गावभाग व माधवनगर',
                    'primary_booths' => 'बूथ क्र. १, बूथ क्र. २, बूथ क्र. ३',
                    'epic'           => 'MH/44/282/' . str_pad($w->id * 1423, 6, '0', STR_PAD_LEFT),
                    'notes'          => 'सक्रिय व विश्वासू कार्यकर्ता',
                ]
            );
        }

        // 2. worker_tasks
        $defaultTasks = [
            [
                'title'     => 'बूथ क्र. १ मधील १० नवीन मतदारांची भेट व पडताळणी',
                'deadline'  => 'आज, सायं ६:००',
                'priority'  => 'High',
                'completed' => false,
                'category'  => 'Voter Verification',
            ],
            [
                'title'     => 'लाडकी बहीण योजनेचे लाभार्थी अर्ज जमा करणे',
                'deadline'  => 'उद्या, दु. १२:००',
                'priority'  => 'Urgent',
                'completed' => true,
                'category'  => 'Welfare Schemes',
            ],
            [
                'title'     => 'प्रभाग कॉर्नर सभेसाठी पत्रके वाटप',
                'deadline'  => '११ ऑक्टोबर',
                'priority'  => 'Medium',
                'completed' => false,
                'category'  => 'Publicity',
            ],
            [
                'title'     => 'स्थानिक ज्येष्ठ नागरिकांशी संवाद दौरा',
                'deadline'  => '१२ ऑक्टोबर',
                'priority'  => 'Normal',
                'completed' => false,
                'category'  => 'Community Outreach',
            ],
        ];

        foreach ($defaultTasks as $t) {
            DB::table('worker_tasks')->updateOrInsert(
                ['title' => $t['title']],
                array_merge($t, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 3. worker_follow_ups
        $defaultFollowUps = [
            [
                'citizen_name' => 'सतीश विठ्ठल खोत',
                'phone'        => '9822441199',
                'topic'        => 'पाणी पुरवठा तक्रार पाठपुरावा',
                'next_date'    => 'आज, दुपारी ५:००',
                'status'       => 'Pending',
                'notes'        => 'महापालिका पाणीपुरवठा विभागाशी संपर्क साधला आहे',
            ],
            [
                'citizen_name' => 'रामकृष्ण पाटील',
                'phone'        => '9876543211',
                'topic'        => 'शासकीय योजना माहिती व अर्ज',
                'next_date'    => 'उद्या, स. ११:००',
                'status'       => 'Pending',
                'notes'        => 'ज्येष्ठ नागरिक पेन्शन कागदपत्रे तपासणी',
            ],
            [
                'citizen_name' => 'विक्रम शिंदे',
                'phone'        => '9876543215',
                'topic'        => 'EPIC मतदार कार्ड नाव सुधारणा',
                'next_date'    => '११ ऑक्टोबर',
                'status'       => 'Scheduled',
                'notes'        => 'फॉर्म क्र. ८ भरून घेतला',
            ],
        ];

        foreach ($defaultFollowUps as $f) {
            DB::table('worker_follow_ups')->updateOrInsert(
                ['citizen_name' => $f['citizen_name'], 'topic' => $f['topic']],
                array_merge($f, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 4. worker_surveys
        $defaultSurveys = [
            [
                'survey_code'      => 'SRV-WARD-04-A',
                'title_mr'         => 'प्रभाग क्र. ४ मतदार कल व नागरी समस्या पाहणी',
                'title_en'         => 'Ward 4 Voter Sentiment & Civic Issues Survey',
                'ward_name'        => 'प्रभाग क्र. ४ - गावभाग व गणपती पेठ',
                'total_voters'     => 1250,
                'completed_voters' => 840,
                'target_date'      => '१५ ऑक्टोबर २०२६',
                'status'           => 'Active',
                'description'      => 'नागरी सुविधा, रस्ते, पाणी आणि स्थानिक राजकीय कल याविषयी मतदारांचे थेट मत जाणून घेणे.',
            ],
            [
                'survey_code'      => 'SRV-SCHEME-02',
                'title_mr'         => 'शासकीय कल्याणकारी योजना लाभार्थी सर्वेक्षण',
                'title_en'         => 'Government Welfare Beneficiary Survey',
                'ward_name'        => 'माधवनगर व वसंत कॉलनी (बूथ २ व ३)',
                'total_voters'     => 800,
                'completed_voters' => 610,
                'target_date'      => '२० ऑक्टोबर २०२६',
                'status'           => 'Active',
                'description'      => 'लाडकी बहीण योजना, शेतकरी सन्मान निधी आणि मोफत आरोग्य तपासणी योजनांचे प्रत्यक्ष लाभ पडताळणी.',
            ],
            [
                'survey_code'      => 'SRV-YOUTH-01',
                'title_mr'         => 'नवमतदार व युवा रोजगार पाहणी',
                'title_en'         => 'First-time Voters & Youth Employment Survey',
                'ward_name'        => 'सांगली शहर प्रभाग संकुल',
                'total_voters'     => 500,
                'completed_voters' => 312,
                'target_date'      => '२५ ऑक्टोबर २०२६',
                'status'           => 'Active',
                'description'      => '१८ ते २५ वयोगटातील नवमतदारांच्या आकांक्षा आणि रोजगार समस्यांची माहिती संकलन.',
            ],
        ];

        foreach ($defaultSurveys as $s) {
            DB::table('worker_surveys')->updateOrInsert(
                ['survey_code' => $s['survey_code']],
                array_merge($s, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 5. worker_complaints
        $defaultComplaints = [
            [
                'complaint_code' => 'C-1001',
                'name'           => 'सतीश विठ्ठल खोत',
                'phone'          => '+91 98224 41199',
                'category'       => 'पाणी पुरवठा',
                'desc'           => 'माधवनगर ब्लॉक ४ येथे २ दिवसांपासून पाण्याचा दाब कमी आहे',
                'date_reported'  => '०८ ऑक्टोबर २०२६',
                'status'         => 'Pending',
                'notes'          => 'पाणी पुरवठा अभियंत्यांकडे पाठपुरावा सुरू आहे',
            ],
            [
                'complaint_code' => 'C-1002',
                'name'           => 'रेखा पांडुरंग सावंत',
                'phone'          => '+91 94215 56677',
                'category'       => 'रस्ता डांबरीकरण',
                'desc'           => 'वसंत कॉलनी मुख्य रस्त्यावरील खड्डे बुजविणे आवश्यक',
                'date_reported'  => '०७ ऑक्टोबर २०२६',
                'status'         => 'Resolved',
                'notes'          => 'महानगरपालिकेकडून डांबरीकरणाचे काम पूर्ण झाले',
            ],
            [
                'complaint_code' => 'C-1003',
                'name'           => 'योगेश दीपक कदम',
                'phone'          => '+91 97641 12233',
                'category'       => 'कचरा व्यवस्थापन',
                'desc'           => 'गावभाग परिसरात घंटागाडी वेळेवर येत नाही',
                'date_reported'  => '०५ ऑक्टोबर २०२६',
                'status'         => 'In Progress',
                'notes'          => 'आरोग्य निरीक्षकांशी चर्चा करून नवीन वेळ निश्चित केली जात आहे',
            ],
            [
                'complaint_code' => 'C-1004',
                'name'           => 'पूजा सुरेश रानडे',
                'phone'          => '+91 99887 45678',
                'category'       => 'स्ट्रीट लाईट',
                'desc'           => 'शाळेसमोरील ३ पथदिवे बंद आहेत',
                'date_reported'  => '०३ ऑक्टोबर २०२६',
                'status'         => 'Pending',
                'notes'          => 'विद्युत विभागाकडे लेखी मागणी नोंदवली आहे',
            ],
        ];

        foreach ($defaultComplaints as $c) {
            DB::table('worker_complaints')->updateOrInsert(
                ['complaint_code' => $c['complaint_code']],
                array_merge($c, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 6. worker_contacts
        $defaultContacts = [
            ['name' => 'अंजली वर्मा', 'phone' => '9876543210', 'ward' => 'बूथ क्र. १', 'status' => 'Supportive', 'contacted' => true],
            ['name' => 'रामकृष्ण पाटील', 'phone' => '9876543211', 'ward' => 'बूथ क्र. १', 'status' => 'Needs Info', 'contacted' => true],
            ['name' => 'प्रिया शर्मा', 'phone' => '9876543212', 'ward' => 'बूथ क्र. २', 'status' => 'Undecided', 'contacted' => false],
            ['name' => 'अमित कदम', 'phone' => '9876543213', 'ward' => 'बूथ क्र. २', 'status' => 'Supportive', 'contacted' => true],
            ['name' => 'नेहा गुप्ता', 'phone' => '9876543214', 'ward' => 'बूथ क्र. ३', 'status' => 'Opposed', 'contacted' => true],
            ['name' => 'विक्रम शिंदे', 'phone' => '9876543215', 'ward' => 'बूथ क्र. ३', 'status' => 'Undecided', 'contacted' => false],
            ['name' => 'स्नेहा पवार', 'phone' => '9876543216', 'ward' => 'बूथ क्र. १', 'status' => 'Supportive', 'contacted' => true],
            ['name' => 'राहुल देसाई', 'phone' => '9876543217', 'ward' => 'बूथ क्र. २', 'status' => 'Needs Info', 'contacted' => true],
        ];

        foreach ($defaultContacts as $contact) {
            DB::table('worker_contacts')->updateOrInsert(
                ['name' => $contact['name'], 'phone' => $contact['phone']],
                array_merge($contact, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 7. worker_meetings
        $defaultMeetings = [
            [
                'title'     => 'बूथ नियोजन व मतदार संपर्क बैठक',
                'time'      => 'आज, दुपारी ४:०० वाजता',
                'location'  => 'प्रभाग क्र. ४ कार्यालय, सांगली',
                'attendees' => '६ कार्यकर्ते',
                'type'      => 'In-person',
                'status'    => 'Scheduled',
            ],
            [
                'title'     => 'साप्ताहिक मोहीम आढावा ऑनलाइन',
                'time'      => 'उद्या, सकाळी १०:३० वाजता',
                'location'  => 'व्हर्च्युअल मीटिंग लिंक',
                'attendees' => '१२ कार्यकर्ते',
                'type'      => 'Virtual',
                'status'    => 'Scheduled',
            ],
            [
                'title'     => 'नवीन स्वयंसेवक प्रशिक्षण शिबिर',
                'time'      => '१२ ऑक्टोबर, दुपारी २:०० वाजता',
                'location'  => 'समाज मंदिर सभागृह',
                'attendees' => '२५ कार्यकर्ते',
                'type'      => 'In-person',
                'status'    => 'Scheduled',
            ],
        ];

        foreach ($defaultMeetings as $m) {
            DB::table('worker_meetings')->updateOrInsert(
                ['title' => $m['title']],
                array_merge($m, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 8. worker_candidates
        $defaultCandidates = [
            [
                'name'        => 'अमोल रामचंद्र पाटील',
                'party'       => 'विकास आघाडी',
                'symbol'      => '🌟',
                'description' => 'प्रभाग विकास आणि पारदर्शक प्रशासनासाठी कटिबद्ध लोकप्रतिनिधी.',
                'photo'       => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
                'about'       => 'गेल्या १० वर्षांपासून सामाजिक आणि राजकीय क्षेत्रात अखंड कार्यरत. त्यांनी प्रभागात आरोग्य शिबिरे आणि मोफत अभ्यासिका सुरू केल्या आहेत.',
                'vision'      => 'स्मार्ट प्रभाग, २४ तास स्वच्छ पाणी, उच्च दर्जाचे रस्ते आणि महिला सुरक्षा केंद्रे.',
                'experience'  => '१० वर्षे सामाजिक कार्य, ५ वर्षे माजी नगरसेवक.',
                'supporters'  => 'स्थानिक युवक मंडळे, शेतकरी संघटना आणि नागरिक मंच.',
                'votes_count' => 142,
            ],
            [
                'name'        => 'सुरेश बापूराव देशमुख',
                'party'       => 'महायुती / भाजप',
                'symbol'      => '🪷',
                'description' => 'मजबूत संघटन आणि पायाभूत सुविधांच्या विकासासाठी सातत्यपूर्ण कार्य.',
                'photo'       => 'https://images.unsplash.com/photo-1540910419892-4a36d2c3266c?auto=format&fit=crop&w=150&q=80',
                'about'       => 'सुरेश देशमुख यांनी तळागाळातील कार्यकर्त्यांना सोबत घेऊन प्रभागात रस्ते आणि पाणी योजना राबविल्या आहेत.',
                'vision'      => 'प्रभागातील प्रत्येक गल्लीत सिमेंट रस्ते, २४ तास पाणीपुरवठा आणि सुरक्षित महिला वातावरण.',
                'experience'  => '१५ वर्षे नगरसेवक व पक्ष संघटक.',
                'supporters'  => 'व्यापारी मंडळ, महिला बचत गट आणि ज्येष्ठ नागरिक संघ.',
                'votes_count' => 128,
            ],
            [
                'name'        => 'अरुण जयवंत मुंडे',
                'party'       => 'शिवसेना',
                'symbol'      => '🏹',
                'description' => 'युवा सक्षमीकरण आणि क्रीडा संकुलांच्या उभारणीसाठी पुढाकार.',
                'photo'       => 'https://images.unsplash.com/photo-1523287562758-66c7fc58967f?auto=format&fit=crop&w=150&q=80',
                'about'       => 'अरुण मुंडे हे तरुण कार्यकर्त्यांचे नेतृत्व करतात. त्यांनी गरजू विद्यार्थ्यांसाठी अभ्यासिका उभारल्या आहेत.',
                'vision'      => 'मोफत स्पर्धा परीक्षा केंद्र, क्रीडा संकुल आणि आरोग्य शिबिरे.',
                'experience'  => 'विद्यार्थी संघटना प्रमुख आणि क्रीडा मार्गदर्शक.',
                'supporters'  => 'विद्यार्थी संघटना आणि तरुण क्रीडा मंडळे.',
                'votes_count' => 95,
            ],
            [
                'name'        => 'विशाल संभाजी पवार',
                'party'       => 'राष्ट्रवादी काँग्रेस',
                'symbol'      => '⏰',
                'description' => 'कृषी, सहकार आणि स्थानिक उद्योगांना प्रोत्साहन देणारे नेतृत्व.',
                'photo'       => 'https://images.unsplash.com/photo-1512314889357-e157c22f938d?auto=format&fit=crop&w=150&q=80',
                'about'       => 'विशाल पवार यांचा सहकार क्षेत्रात मोठा अनुभव असून त्यांनी शेतकरी ते ग्राहक थेट बाजार सुरू केला.',
                'vision'      => 'सहकारी तत्त्वावर रोजगार निर्मिती आणि शेतीमालाला थेट बाजारपेठ.',
                'experience'  => '८ वर्षे सहकारी बँक संचालक आणि कृषी मंच अध्यक्ष.',
                'supporters'  => 'कृषी उत्पन्न बाजार समिती कार्यकर्ते आणि महिला मंच.',
                'votes_count' => 84,
            ],
        ];

        foreach ($defaultCandidates as $c) {
            DB::table('worker_candidates')->updateOrInsert(
                ['name' => $c['name']],
                array_merge($c, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 9. worker_attendances
        DB::table('worker_attendances')->updateOrInsert(
            ['worker_id' => 3, 'date' => '०८ ऑक्टोबर २०२६'],
            [
                'punch_in_time'  => '०९:१५ AM',
                'punch_out_time' => '०६:३० PM',
                'status'         => 'Present',
                'location_note'  => 'सांगली प्रभाग कार्यालय ४',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        );

        DB::table('worker_attendances')->updateOrInsert(
            ['worker_id' => 3, 'date' => '०७ ऑक्टोबर २०२६'],
            [
                'punch_in_time'  => '०९:०० AM',
                'punch_out_time' => '०६:०० PM',
                'status'         => 'Present',
                'location_note'  => 'सांगली प्रभाग कार्यालय ४',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        );

        // 10. worker_activities
        $defaultActivities = [
            ['type' => 'users', 'bg_color' => '#E0E7FF', 'color' => '#F97316', 'title' => 'अंजली वर्मा यांच्याशी संपर्क पूर्ण केला (अनुकूल मतदार)', 'time' => '२ तासांपूर्वी • बूथ १'],
            ['type' => 'clock', 'bg_color' => '#FEF3C7', 'color' => '#F59E0B', 'title' => 'रामकृष्ण पाटील यांच्याशी भेटीचा पाठपुरावा नियोजित', 'time' => '४ तासांपूर्वी'],
            ['type' => 'clipboard', 'bg_color' => '#DCFCE7', 'color' => '#16A34A', 'title' => 'बूथ क्र. १ ची आढावा बैठक यशस्वीरीत्या संपन्न', 'time' => 'काल, संध्याकाळी ५:००'],
        ];

        foreach ($defaultActivities as $act) {
            DB::table('worker_activities')->updateOrInsert(
                ['title' => $act['title']],
                array_merge($act, [
                    'worker_id'  => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
