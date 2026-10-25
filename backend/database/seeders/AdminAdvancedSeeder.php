<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminAdvancedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds initial records for Admin Advanced modules:
     * 1. Data Approval & Correction
     * 2. Database Backup & Restore records
     * 3. Duplicate Detection Flags
     * 4. Media & Document Manager
     * 5. Security Sessions & Monitoring
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('admin_field_submissions')->truncate();
        DB::table('admin_system_backups')->truncate();
        DB::table('admin_duplicate_flags')->truncate();
        DB::table('admin_media_documents')->truncate();
        DB::table('admin_security_sessions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Data Approval & Correction Submissions
        $submissions = [
            [
                'id' => 1,
                'submission_code' => 'SUB-2026-001',
                'form_code' => 'SURVEY_GENERAL',
                'form_title_mr' => 'मतदार व कुटुंब पाहणी फॉर्म',
                'citizen_name_mr' => 'सतीश विठ्ठल खोत',
                'voter_id_number' => 'MH/44/282/019482',
                'phone' => '9822441199',
                'booth_no' => 'बूथ क्र. १',
                'village_mr' => 'माधवनगर पूर्व',
                'taluka_mr' => 'माधवनगर व उपनगरे',
                'submitted_by_name' => 'सचिन दत्तात्रय पाटील',
                'submission_data' => json_encode([
                    'age' => 48,
                    'gender' => 'पुरुष',
                    'occupation' => 'शेती व दुग्धव्यवसाय',
                    'family_voters_count' => 4,
                    'sentiment' => 'अनुकूल',
                    'schemes_benefitted' => ['पीएम किसान', 'नमो शेतकरी सन्मान'],
                    'main_issue' => 'शेतीसाठी विजेचा दाब कमी असणे व दिवसा वीज पुरवठा मागणी',
                ]),
                'status' => 'PENDING_APPROVAL',
                'reviewed_by' => null,
                'rejection_reason' => null,
                'correction_notes' => null,
                'verified_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => 2,
                'submission_code' => 'SUB-2026-002',
                'form_code' => 'SURVEY_GENERAL',
                'form_title_mr' => 'मतदार व कुटुंब पाहणी फॉर्म',
                'citizen_name_mr' => 'रेखा पांडुरंग सावंत',
                'voter_id_number' => 'MH/44/282/021944',
                'phone' => '9421556677',
                'booth_no' => 'बूथ क्र. ३',
                'village_mr' => 'वसंत कॉलनी प्रभाग ४',
                'taluka_mr' => 'सांगली शहर व प्रभाग',
                'submitted_by_name' => 'अमोल सुरेश जाधव',
                'submission_data' => json_encode([
                    'age' => 38,
                    'gender' => 'महिला',
                    'occupation' => 'गृहउद्योग',
                    'family_voters_count' => 3,
                    'sentiment' => 'अनुकूल',
                    'schemes_benefitted' => ['मुख्यमंत्री माझी लाडकी बहीण योजना'],
                    'main_issue' => 'गल्लीतील कचरा कुंडी नियमित उचलणे',
                ]),
                'status' => 'PENDING_APPROVAL',
                'reviewed_by' => null,
                'rejection_reason' => null,
                'correction_notes' => null,
                'verified_at' => null,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
            [
                'id' => 3,
                'submission_code' => 'SUB-2026-003',
                'form_code' => 'SURVEY_YOUTH',
                'form_title_mr' => 'युवक रोजगार व शिक्षण पाहणी',
                'citizen_name_mr' => 'योगेश दीपक कदम',
                'voter_id_number' => 'MH/44/282/031200',
                'phone' => '9764112233',
                'booth_no' => 'बूथ क्र. ४',
                'village_mr' => 'गावभाग प्रभाग ७',
                'taluka_mr' => 'सांगली शहर व प्रभाग',
                'submitted_by_name' => 'किरण मारुती शिंदे',
                'submission_data' => json_encode([
                    'age' => 21,
                    'gender' => 'पुरुष',
                    'occupation' => 'विद्यार्थी (इंजिनिअरिंग)',
                    'family_voters_count' => 3,
                    'sentiment' => 'तटस्थ',
                    'schemes_benefitted' => [],
                    'main_issue' => 'सांगली शहरात स्पर्धा परीक्षा अभ्यासिका व वायफाय केंद्र हवे',
                ]),
                'status' => 'APPROVED',
                'reviewed_by' => 'प्रशासक (Admin HQ)',
                'rejection_reason' => null,
                'correction_notes' => 'डेटा पडताळणी पूर्ण झाली व मास्टर डेटामध्ये समक्रमित केला.',
                'verified_at' => now()->subDay(),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDay(),
            ],
            [
                'id' => 4,
                'submission_code' => 'SUB-2026-004',
                'form_code' => 'SURVEY_GENERAL',
                'form_title_mr' => 'मतदार व कुटुंब पाहणी फॉर्म',
                'citizen_name_mr' => 'महादेव आत्माराम साळुंखे',
                'voter_id_number' => 'MH/44/282/001429', // Intentionally duplicate with ID 1
                'phone' => '9822145890',
                'booth_no' => 'बूथ क्र. ५',
                'village_mr' => 'कुपवाड प्रभाग ११',
                'taluka_mr' => 'कुपवाड औद्योगिक वसाहत',
                'submitted_by_name' => 'अविनाश विठ्ठल मोहिते',
                'submission_data' => json_encode([
                    'age' => 52,
                    'gender' => 'पुरुष',
                    'occupation' => 'कामगार',
                    'family_voters_count' => 5,
                    'sentiment' => 'तटस्थ',
                    'schemes_benefitted' => [],
                    'main_issue' => 'रस्त्यावरील दिवे बंद असणे',
                ]),
                'status' => 'REJECTED',
                'reviewed_by' => 'प्रशासक (Admin HQ)',
                'rejection_reason' => 'EPIC मतदार ओळखपत्र क्रमांक (MH/44/282/001429) बूथ क्र. १ वरील बाळासाहेब पाटील यांच्याशी जुळतो. कृपया मतदार ओळखपत्र पुन्हा तपासा.',
                'correction_notes' => 'मतदार ओळखपत्र चुकीचे भरले होते.',
                'verified_at' => now()->subDay(),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDay(),
            ],
        ];

        DB::table('admin_field_submissions')->insert($submissions);

        // 2. Database Backup & Restore records
        $backups = [
            [
                'filename' => 'political_project_db_snapshot_2026_10_07.sql',
                'file_path' => 'backups/political_project_db_snapshot_2026_10_07.sql',
                'file_size_bytes' => 1485900,
                'file_size_formatted' => '1.42 MB',
                'backup_type' => 'FULL_SQL',
                'status' => 'SUCCESS',
                'created_by_name' => 'System Auto-Backup',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'filename' => 'political_project_db_snapshot_2026_10_01.sql',
                'file_path' => 'backups/political_project_db_snapshot_2026_10_01.sql',
                'file_size_bytes' => 1290300,
                'file_size_formatted' => '1.23 MB',
                'backup_type' => 'FULL_SQL',
                'status' => 'SUCCESS',
                'created_by_name' => 'Super Admin',
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],
        ];

        DB::table('admin_system_backups')->insert($backups);

        // 3. Duplicate Detection Flags
        $duplicateFlags = [
            [
                'detection_type' => 'VOTER_ID_DUPLICATE',
                'identifier_value' => 'MH/44/282/001429',
                'primary_record_id' => 1,
                'primary_record_table' => 'neta_citizens',
                'duplicate_record_id' => 4,
                'duplicate_record_table' => 'admin_field_submissions',
                'record_title_mr' => 'बाळासाहेब मारुती पाटील विरुद्ध महादेव आत्माराम साळुंखे',
                'booth_conflict_mr' => 'बूथ क्र. १ (माधवनगर) vs बूथ क्र. ५ (कुपवाड)',
                'similarity_score' => 100,
                'status' => 'DETECTED',
                'notes_mr' => 'एकाच EPIC क्रमांकावर दोन वेगवेगळ्या बुथांवर नोंदणी आढळली.',
                'resolved_by' => null,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'detection_type' => 'PHONE_DUPLICATE',
                'identifier_value' => '9822457812',
                'primary_record_id' => 2,
                'primary_record_table' => 'neta_citizens',
                'duplicate_record_id' => 5,
                'duplicate_record_table' => 'neta_citizens',
                'record_title_mr' => 'प्रवीण रमेश माने (कार्यकर्ता) हाच फोन मतदार रेकॉर्डमध्ये जोडला',
                'booth_conflict_mr' => 'बूथ क्र. २ अंतर्गत फोन डुप्लिकेशन',
                'similarity_score' => 95,
                'status' => 'DETECTED',
                'notes_mr' => 'कार्यकर्त्याने स्वतःचा फोन नंबर मतदाराच्या रकान्यात टाकला आहे.',
                'resolved_by' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'detection_type' => 'NAME_SIMILARITY',
                'identifier_value' => 'पाटील सचिन दत्तात्रय',
                'primary_record_id' => 1,
                'primary_record_table' => 'neta_booth_progress',
                'duplicate_record_id' => 3,
                'duplicate_record_table' => 'neta_karyakartas',
                'record_title_mr' => 'सचिन दत्तात्रय पाटील vs सचिन डी. पाटील',
                'booth_conflict_mr' => 'बूथ क्र. १ प्रभारी मॅपिंग साम्य',
                'similarity_score' => 88,
                'status' => 'RESOLVED_MERGED',
                'notes_mr' => 'दोन्ही नोंदी एकाच व्यक्तीच्या असल्याचे तपासून एकत्र करण्यात आले.',
                'resolved_by' => 'प्रशासक (Admin HQ)',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(2),
            ],
        ];

        DB::table('admin_duplicate_flags')->insert($duplicateFlags);

        // 4. Central Document Manager (कागदपत्र व्यवस्थापक)
        $documents = [
            [
                'title_mr' => 'लाडकी बहीण योजना अधिकृत अर्ज व आधार पडताळणी दस्तऐवज',
                'category' => 'application_form',
                'file_name' => 'ladki_bahin_form_scan.pdf',
                'file_url' => '/documents/ladki_bahin_form_scan.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '1.2 MB',
                'booth_ref' => 'बूथ क्र. ३',
                'citizen_name_ref' => 'सुनिता रमेश गायकवाड',
                'uploaded_by_name' => 'अमोल सुरेश जाधव',
                'tags' => 'लाडकी बहीण, शासकीय योजना, आधार',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title_mr' => 'माधवनगर पेठ मुख्य ड्रेनेज तक्रार निवारण अधिकृत निवेदन पत्र',
                'category' => 'application_form',
                'file_name' => 'drainage_madhavnagar_application.pdf',
                'file_url' => '/documents/drainage_madhavnagar_application.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '340 KB',
                'booth_ref' => 'बूथ क्र. १',
                'citizen_name_ref' => 'बाळासाहेब मारुती पाटील',
                'uploaded_by_name' => 'सचिन दत्तात्रय पाटील',
                'tags' => 'ड्रेनेज, पाणीपुरवठा, मनपा निवेदन',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'title_mr' => 'कुपवाड औद्योगिक रस्ता दुरुस्ती व डांबरीकरण मागणी अर्ज',
                'category' => 'application_form',
                'file_name' => 'kupwad_road_petition.pdf',
                'file_url' => '/documents/kupwad_road_petition.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '520 KB',
                'booth_ref' => 'बूथ क्र. ५',
                'citizen_name_ref' => 'रफिक हुसेन मुल्ला',
                'uploaded_by_name' => 'अविनाश विठ्ठल मोहिते',
                'tags' => 'रस्ता, डांबरीकरण, एमआयडीसी',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title_mr' => 'मतदार यादी नाव नोंदणी व पत्ता दुरुस्ती नमुना अर्ज क्र. ६ व पुरावा',
                'category' => 'voter_id_proof',
                'file_name' => 'voter_registration_form6.pdf',
                'file_url' => '/documents/voter_registration_form6.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '450 KB',
                'booth_ref' => 'बूथ क्र. २',
                'citizen_name_ref' => 'विनायक मारुती सावंत',
                'uploaded_by_name' => 'बूथ समन्वयक',
                'tags' => 'मतदार नोंदणी, फॉर्म ६, निवडणूक ओळखपत्र',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'title_mr' => 'सांगली विधानसभा मतदारसंघ बूथनिहाय प्राधान्य व सर्वेक्षण अहवाल',
                'category' => 'survey_report',
                'file_name' => 'sangli_assembly_booth_survey_2026.pdf',
                'file_url' => '/documents/sangli_assembly_booth_survey_2026.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '2.4 MB',
                'booth_ref' => 'सर्व बूथ',
                'citizen_name_ref' => 'संशोधन व सर्व्हे पथक',
                'uploaded_by_name' => 'डेटा ॲनॅलिस्ट',
                'tags' => 'सर्वेक्षण, अहवाल, मतदार कल',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title_mr' => 'पक्ष संघटना व बूथ समन्वय समिती नियुक्ती अधिकृत परिपत्रक',
                'category' => 'party_circular',
                'file_name' => 'party_circular_booth_incharge_order.pdf',
                'file_url' => '/documents/party_circular_booth_incharge_order.pdf',
                'file_type' => 'application/pdf',
                'file_size_formatted' => '680 KB',
                'booth_ref' => 'सांगली मध्यवर्ती कार्यालय',
                'citizen_name_ref' => 'सर्व बूथप्रमुख व पदाधिकारी',
                'uploaded_by_name' => 'मुख्यालय प्रभारी',
                'tags' => 'परिपत्रक, नियुक्ती, संघटना',
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
        ];

        DB::table('admin_media_documents')->insert($documents);

        // 5. Security Monitoring & Active Sessions
        $sessions = [
            [
                'user_id' => 1,
                'user_name' => 'मुख्य प्रशासक (Super Admin)',
                'role_name' => 'ADMIN',
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0',
                'device_type' => 'Desktop PC (Windows)',
                'location_ip' => 'सांगली, महाराष्ट्र',
                'last_activity_at' => now()->subMinutes(2),
                'status' => 'ACTIVE',
                'is_blocked_ip' => false,
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subMinutes(2),
            ],
            [
                'user_id' => 2,
                'user_name' => 'मा. आमदार / नियंत्रण अधिकारी',
                'role_name' => 'OFFICER',
                'ip_address' => '49.36.120.45',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X)',
                'device_type' => 'Mobile Safari (iOS)',
                'location_ip' => 'मुंबई / सांगली',
                'last_activity_at' => now()->subMinutes(8),
                'status' => 'ACTIVE',
                'is_blocked_ip' => false,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subMinutes(8),
            ],
            [
                'user_id' => 3,
                'user_name' => 'सचिन दत्तात्रय पाटील (बूथ १)',
                'role_name' => 'WORKER',
                'ip_address' => '106.198.88.12',
                'user_agent' => 'Android App v2.4 (Xiaomi Redmi Note 13)',
                'device_type' => 'Android Phone',
                'location_ip' => 'माधवनगर, सांगली',
                'last_activity_at' => now()->subMinutes(14),
                'status' => 'ACTIVE',
                'is_blocked_ip' => false,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subMinutes(14),
            ],
            [
                'user_id' => null,
                'user_name' => 'अनोळखी लॉगिन प्रयत्न (Unknown Attempt)',
                'role_name' => 'UNKNOWN',
                'ip_address' => '103.212.45.198',
                'user_agent' => 'Python-requests/2.28.1 (Automated Bot)',
                'device_type' => 'Automated Script',
                'location_ip' => 'अज्ञात आयपी',
                'last_activity_at' => now()->subHours(3),
                'status' => 'FAILED_ATTEMPT',
                'is_blocked_ip' => true,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
        ];

        DB::table('admin_security_sessions')->insert($sessions);
    }
}
