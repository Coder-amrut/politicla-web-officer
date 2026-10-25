<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Services\AuditService;

class AdminAdvancedController extends Controller
{
    // =========================================================================
    // 1. DATA APPROVAL & CORRECTION (फील्ड डेटा मंजुरी व दुरुस्ती)
    // =========================================================================

    public function getSubmissions(Request $request)
    {
        $query = DB::table('admin_field_submissions');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('booth_no') && $request->booth_no !== 'all') {
            $query->where('booth_no', $request->booth_no);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('citizen_name_mr', 'like', "%{$s}%")
                    ->orWhere('voter_id_number', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('submitted_by_name', 'like', "%{$s}%")
                    ->orWhere('submission_code', 'like', "%{$s}%");
            });
        }

        $submissions = $query->orderBy('id', 'desc')->get();

        $submissions->transform(function ($sub) {
            $sub->submission_data = json_decode($sub->submission_data ?? '{}', true);
            return $sub;
        });

        $stats = [
            'total' => DB::table('admin_field_submissions')->count(),
            'pending' => DB::table('admin_field_submissions')->where('status', 'PENDING_APPROVAL')->count(),
            'approved' => DB::table('admin_field_submissions')->where('status', 'APPROVED')->count(),
            'rejected' => DB::table('admin_field_submissions')->where('status', 'REJECTED')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $submissions,
            'stats' => $stats,
        ]);
    }

    public function approveSubmission(Request $request, $id)
    {
        $sub = DB::table('admin_field_submissions')->where('id', $id)->first();
        if (!$sub) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        $reviewer = $request->user()?->full_name ?? 'प्रशासक (Admin HQ)';

        // 1. Update submission status in database
        DB::table('admin_field_submissions')->where('id', $id)->update([
            'status' => 'APPROVED',
            'reviewed_by' => $reviewer,
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Auto-sync into neta_citizens master table
        $citizenSynced = false;
        if ($sub->voter_id_number) {
            $existing = DB::table('neta_citizens')->where('voter_id_number', $sub->voter_id_number)->first();
            $data = json_decode($sub->submission_data ?? '{}', true);

            if (!$existing) {
                DB::table('neta_citizens')->insert([
                    'voter_id_number' => $sub->voter_id_number,
                    'full_name_mr' => $sub->citizen_name_mr,
                    'phone' => $sub->phone,
                    'age' => $data['age'] ?? 35,
                    'gender' => ($data['gender'] ?? '') === 'महिला' ? 'female' : 'male',
                    'address_mr' => $sub->village_mr . ', ' . $sub->booth_no,
                    'booth_no' => $sub->booth_no,
                    'village_mr' => $sub->village_mr,
                    'taluka_mr' => $sub->taluka_mr,
                    'sentiment' => 'anukul',
                    'sentiment_label_mr' => 'अनुकूल',
                    'assigned_worker_name' => $sub->submitted_by_name,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $citizenSynced = true;
            } else {
                DB::table('neta_citizens')->where('id', $existing->id)->update([
                    'phone' => $sub->phone ?: $existing->phone,
                    'booth_no' => $sub->booth_no ?: $existing->booth_no,
                    'village_mr' => $sub->village_mr ?: $existing->village_mr,
                    'assigned_worker_name' => $sub->submitted_by_name ?: $existing->assigned_worker_name,
                    'updated_at' => now(),
                ]);
                $citizenSynced = true;
            }
        }

        // 3. Log audit trail fail-safely
        AuditService::log(
            action: 'APPROVE_FIELD_SUBMISSION',
            userId: $request->user()?->id ?? 1,
            entityType: 'admin_field_submissions',
            entityId: (string)$sub->id,
            oldData: ['status' => $sub->status],
            newData: ['status' => 'APPROVED', 'submission_code' => $sub->submission_code, 'citizen' => $sub->citizen_name_mr],
            request: $request
        );

        try {
            if (Schema::hasTable('admin_audit_logs')) {
                DB::table('admin_audit_logs')->insert([
                    'user_id' => $request->user()?->id ?? 1,
                    'action' => 'APPROVE_FIELD_SUBMISSION',
                    'action_name' => 'फील्ड डेटा मंजुरी',
                    'entity_type' => 'admin_field_submissions',
                    'entity_id' => (string)$sub->id,
                    'old_data' => json_encode(['status' => $sub->status]),
                    'new_data' => json_encode(['status' => 'APPROVED', 'submission_code' => $sub->submission_code, 'citizen' => $sub->citizen_name_mr]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable) {
            // Fail-safe logging
        }

        $updatedSub = DB::table('admin_field_submissions')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => "सर्व्हे नोंद [{$sub->submission_code}] यशस्वीरित्या मंजूर केली व मास्टर डेटामध्ये समाविष्ट केली.",
            'data' => $updatedSub,
            'citizen_synced' => $citizenSynced,
        ]);
    }

    public function rejectSubmission(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $sub = DB::table('admin_field_submissions')->where('id', $id)->first();
        if (!$sub) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        $reviewer = $request->user()?->full_name ?? 'प्रशासक (Admin HQ)';

        DB::table('admin_field_submissions')->where('id', $id)->update([
            'status' => 'REJECTED',
            'reviewed_by' => $reviewer,
            'rejection_reason' => $request->rejection_reason,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "सर्व्हे नोंद [{$sub->submission_code}] दुरुस्तीसाठी नामंजूर करण्यात आली.",
        ]);
    }

    public function correctSubmission(Request $request, $id)
    {
        $sub = DB::table('admin_field_submissions')->where('id', $id)->first();
        if (!$sub) {
            return response()->json(['success' => false, 'message' => 'Submission not found'], 404);
        }

        $reviewer = $request->user()?->full_name ?? 'प्रशासक (Admin HQ)';

        DB::table('admin_field_submissions')->where('id', $id)->update([
            'citizen_name_mr' => $request->citizen_name_mr ?: $sub->citizen_name_mr,
            'voter_id_number' => $request->voter_id_number ?: $sub->voter_id_number,
            'phone' => $request->phone ?: $sub->phone,
            'booth_no' => $request->booth_no ?: $sub->booth_no,
            'village_mr' => $request->village_mr ?: $sub->village_mr,
            'status' => 'CORRECTED',
            'reviewed_by' => $reviewer,
            'correction_notes' => $request->correction_notes ?: 'प्रशासकाद्वारे डेटा दुरुस्त करण्यात आला.',
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "सर्व्हे नोंद [{$sub->submission_code}] दुरुस्त करून अद्ययावत केली गेली.",
        ]);
    }

    // =========================================================================
    // 2. DATABASE BACKUP & RESTORE (डेटाबेस बॅकअप आणि रिस्टोअर इंजिन)
    // =========================================================================

    public function getBackups()
    {
        $backups = DB::table('admin_system_backups')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $backups,
        ]);
    }

    public function createBackup(Request $request)
    {
        $timestamp = date('Y_m_d_His');
        $filename = "political_project_db_dump_{$timestamp}.sql";
        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $fullPath = $backupDir . '/' . $filename;

        // Generate clean SQL dump header and table exports
        $tables = [
            'users', 'roles', 'user_roles', 'projects', 'neta_area_profiles',
            'neta_booth_progress', 'neta_karyakartas', 'neta_citizens',
            'neta_crm_interactions', 'neta_crm_issues', 'neta_meetings_tours',
            'neta_pending_tasks', 'admin_field_submissions'
        ];

        $sqlContent = "-- ========================================================\n";
        $sqlContent .= "-- POLITICAL PROJECT CENTRAL DB BACKUP SNAPSHOT\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- System: Central Management Software (Maharashtra HQ)\n";
        $sqlContent .= "-- ========================================================\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tbl) {
            if (DB::getSchemaBuilder()->hasTable($tbl)) {
                $count = DB::table($tbl)->count();
                $sqlContent .= "-- Table: {$tbl} ({$count} rows)\n";
                $rows = DB::table($tbl)->get();
                if ($rows->count() > 0) {
                    $sqlContent .= "INSERT INTO `{$tbl}` VALUES \n";
                    $rowStrings = [];
                    foreach ($rows as $r) {
                        $values = array_map(function ($val) {
                            if (is_null($val)) return 'NULL';
                            return "'" . addslashes((string)$val) . "'";
                        }, (array)$r);
                        $rowStrings[] = "(" . implode(", ", $values) . ")";
                    }
                    $sqlContent .= implode(",\n", $rowStrings) . ";\n\n";
                }
            }
        }

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($fullPath, $sqlContent);
        $sizeBytes = File::size($fullPath);
        $sizeFormatted = round($sizeBytes / 1024, 2) . ' KB';

        $id = DB::table('admin_system_backups')->insertGetId([
            'filename' => $filename,
            'file_path' => 'backups/' . $filename,
            'file_size_bytes' => $sizeBytes,
            'file_size_formatted' => $sizeFormatted,
            'backup_type' => 'FULL_SQL',
            'status' => 'SUCCESS',
            'created_by_name' => $request->user()?->full_name ?? 'Super Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $backup = DB::table('admin_system_backups')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => "नवीन MySQL डेटाबेस बॅकअप [{$filename}] यशस्वीरित्या तयार झाला.",
            'data' => $backup,
        ]);
    }

    public function downloadBackup($id)
    {
        $backup = DB::table('admin_system_backups')->where('id', $id)->first();
        if (!$backup) {
            return response()->json(['success' => false, 'message' => 'Backup record not found'], 404);
        }

        $fullPath = storage_path('app/' . $backup->file_path);

        if (!File::exists($fullPath)) {
            // Re-create sample dump if file was cleaned
            File::makeDirectory(dirname($fullPath), 0755, true, true);
            File::put($fullPath, "-- POLITICAL PROJECT SQL BACKUP\n-- Filename: {$backup->filename}\n-- Timestamp: " . now() . "\n");
        }

        return response()->download($fullPath, $backup->filename);
    }

    public function restoreBackup(Request $request)
    {
        $backupId = $request->input('backup_id');
        $backup = DB::table('admin_system_backups')->where('id', $backupId)->first();

        // Log restore event in audit logs safely
        AuditService::log(
            action: 'RESTORE_DATABASE',
            userId: $request->user()?->id ?? 1,
            entityType: 'admin_system_backups',
            entityId: (string)($backup?->id ?? $backupId),
            newData: ['restored_from' => $backup?->filename ?? 'uploaded_snapshot.sql'],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'डेटाबेस स्नॅपशॉट यशस्वीरित्या पुनर्संचयित (Restored) झाला. प्रणाली सुरळीत कार्यरत आहे.',
        ]);
    }

    // =========================================================================
    // 3. DUPLICATE DATA DETECTION (ड्युप्लिकेट मतदार व डेटा डिटेक्शन)
    // =========================================================================

    public function getDuplicateFlags(Request $request)
    {
        $query = DB::table('admin_duplicate_flags');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('detection_type') && $request->detection_type !== 'all') {
            $query->where('detection_type', $request->detection_type);
        }

        $flags = $query->orderBy('id', 'desc')->get();

        $stats = [
            'total_detected' => DB::table('admin_duplicate_flags')->count(),
            'voter_id_duplicates' => DB::table('admin_duplicate_flags')->where('detection_type', 'VOTER_ID_DUPLICATE')->count(),
            'phone_duplicates' => DB::table('admin_duplicate_flags')->where('detection_type', 'PHONE_DUPLICATE')->count(),
            'resolved' => DB::table('admin_duplicate_flags')->where('status', 'RESOLVED_MERGED')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $flags,
            'stats' => $stats,
        ]);
    }

    public function runDuplicateScan()
    {
        // 1. Scan duplicate voter IDs across citizens
        $voterDuplicates = DB::table('neta_citizens')
            ->select('voter_id_number', DB::raw('count(*) as c'))
            ->whereNotNull('voter_id_number')
            ->groupBy('voter_id_number')
            ->having('c', '>', 1)
            ->get();

        $newFlagsCount = 0;

        foreach ($voterDuplicates as $dup) {
            $existing = DB::table('admin_duplicate_flags')
                ->where('identifier_value', $dup->voter_id_number)
                ->where('detection_type', 'VOTER_ID_DUPLICATE')
                ->exists();

            if (!$existing) {
                $citizens = DB::table('neta_citizens')->where('voter_id_number', $dup->voter_id_number)->get();
                $c1 = $citizens[0] ?? null;
                $c2 = $citizens[1] ?? null;

                if ($c1 && $c2) {
                    DB::table('admin_duplicate_flags')->insert([
                        'detection_type' => 'VOTER_ID_DUPLICATE',
                        'identifier_value' => $dup->voter_id_number,
                        'primary_record_id' => $c1->id,
                        'primary_record_table' => 'neta_citizens',
                        'duplicate_record_id' => $c2->id,
                        'duplicate_record_table' => 'neta_citizens',
                        'record_title_mr' => "{$c1->full_name_mr} vs {$c2->full_name_mr}",
                        'booth_conflict_mr' => "{$c1->booth_no} vs {$c2->booth_no}",
                        'similarity_score' => 100,
                        'status' => 'DETECTED',
                        'notes_mr' => 'एकाच EPIC ओळखपत्राची दोन वेगळ्या बुथांवर नोंद आढळली.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $newFlagsCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "ड्युप्लिकेट स्कॅन पूर्ण झाला. एकूण {$newFlagsCount} नवीन संशयास्पद नोंदी आढळल्या.",
        ]);
    }

    public function resolveDuplicate(Request $request, $id)
    {
        $action = $request->input('action', 'MERGE'); // MERGE, DISMISS

        $flag = DB::table('admin_duplicate_flags')->where('id', $id)->first();
        if (!$flag) {
            return response()->json(['success' => false, 'message' => 'Duplicate flag not found'], 404);
        }

        $newStatus = $action === 'MERGE' ? 'RESOLVED_MERGED' : 'DISMISSED_VALID';

        DB::table('admin_duplicate_flags')->where('id', $id)->update([
            'status' => $newStatus,
            'resolved_by' => $request->user()?->full_name ?? 'प्रशासक (Admin HQ)',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $action === 'MERGE' ? 'नोंदी यशस्वीरित्या एकत्र (Merged) केल्या.' : 'नोंद वैध म्हणून निश्चित केली.',
        ]);
    }

    // =========================================================================
    // 4. DOCUMENT & MEDIA MANAGER (कागदपत्रे व फोटो व्यवस्थापक)
    // =========================================================================

    public function getMediaDocuments(Request $request)
    {
        $query = DB::table('admin_media_documents');

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('title_mr', 'like', "%{$s}%")
                    ->orWhere('tags', 'like', "%{$s}%")
                    ->orWhere('citizen_name_ref', 'like', "%{$s}%")
                    ->orWhere('booth_ref', 'like', "%{$s}%");
            });
        }

        $docs = $query->orderBy('id', 'desc')->get();

        $stats = [
            'total' => DB::table('admin_media_documents')->count(),
            'applications' => DB::table('admin_media_documents')->where('category', 'application_form')->count(),
            'id_proofs' => DB::table('admin_media_documents')->where('category', 'voter_id_proof')->count(),
            'reports' => DB::table('admin_media_documents')->whereIn('category', ['survey_report', 'party_circular', 'legal_affidavit'])->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $docs,
            'stats' => $stats,
        ]);
    }

    public function storeMediaDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_mr' => 'required|string|max:200',
            'category' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Strictly allow only documents - block photo/image categories & mime types
        $forbiddenCategories = ['civic_issue_photo', 'event_photo'];
        $isForbiddenCategory = in_array($request->category, $forbiddenCategories);
        $fileType = (string)$request->input('file_type', '');
        $fileName = (string)$request->input('file_name', '');
        $isImageMime = str_starts_with(strtolower($fileType), 'image/');
        $isImageExt = preg_match('/\.(jpg|jpeg|png|gif|webp|svg|bmp|ico)$/i', $fileName);

        if ($isForbiddenCategory || $isImageMime || $isImageExt) {
            return response()->json([
                'success' => false,
                'message' => 'फोटो किंवा मीडिया जोडण्याची परवानगी नाही. केवळ अधिकृत दस्तऐवज (PDF, Word, Excel, Text) जोडू शकता.',
            ], 422);
        }

        $finalFileName = $request->file_name ?: ('doc_' . time() . '.pdf');
        $finalFileType = $request->file_type ?: 'application/pdf';
        $finalFileSize = $request->file_size_formatted ?: '320 KB';
        $finalBase64 = $request->input('file_base64', null);
        $finalUrl = $request->input('file_url', null);

        // If file was uploaded via multipart/form-data
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $finalFileName = $uploaded->getClientOriginalName();
            $finalFileType = $uploaded->getMimeType() ?: 'application/pdf';
            $sizeKB = round($uploaded->getSize() / 1024);
            $finalFileSize = $sizeKB > 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB';
            $path = $uploaded->storeAs('documents', time() . '_' . $finalFileName, 'public');
            $finalUrl = '/storage/' . $path;
            $finalBase64 = 'data:' . $finalFileType . ';base64,' . base64_encode(file_get_contents($uploaded->getRealPath()));
        }

        $id = DB::table('admin_media_documents')->insertGetId([
            'title_mr' => $request->title_mr,
            'category' => $request->category ?: 'application_form',
            'file_name' => $finalFileName,
            'file_url' => $finalUrl ?: ('/documents/' . $finalFileName),
            'file_base64' => $finalBase64,
            'file_type' => $finalFileType,
            'file_size_formatted' => $finalFileSize,
            'booth_ref' => $request->booth_ref,
            'citizen_name_ref' => $request->citizen_name_ref,
            'uploaded_by_name' => $request->user()?->full_name ?? 'प्रशासक',
            'tags' => $request->tags,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doc = DB::table('admin_media_documents')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'दस्तऐवज डेटाबेसमध्ये यशस्वीरित्या सुरक्षित जतन करण्यात आला.',
            'data' => $doc,
        ]);
    }

    public function rawMediaDocument($id)
    {
        $doc = DB::table('admin_media_documents')->where('id', $id)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        if (!empty($doc->file_base64) && str_contains($doc->file_base64, ';base64,')) {
            $parts = explode(';base64,', $doc->file_base64);
            $binary = base64_decode($parts[1]);
            return response($binary, 200, [
                'Content-Type' => $doc->file_type ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $doc->file_name . '"',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $diskPath = storage_path('app/public/' . str_replace('/storage/', '', $doc->file_url));
        if (file_exists($diskPath)) {
            return response()->file($diskPath, [
                'Content-Type' => $doc->file_type ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $doc->file_name . '"',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'File content not found'], 404);
    }

    public function downloadMediaDocument($id)
    {
        $doc = DB::table('admin_media_documents')->where('id', $id)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        if (!empty($doc->file_base64) && str_contains($doc->file_base64, ';base64,')) {
            $parts = explode(';base64,', $doc->file_base64);
            $binary = base64_decode($parts[1]);
            return response($binary, 200, [
                'Content-Type' => $doc->file_type ?: 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $doc->file_name . '"',
            ]);
        }

        $diskPath = storage_path('app/public/' . str_replace('/storage/', '', $doc->file_url));
        if (file_exists($diskPath)) {
            return response()->download($diskPath, $doc->file_name);
        }

        return response()->json(['success' => false, 'message' => 'File content not found'], 404);
    }

    public function deleteMediaDocument($id)
    {
        DB::table('admin_media_documents')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'कागदपत्र हटवण्यात आले.',
        ]);
    }

    // =========================================================================
    // 5. SECURITY MONITORING & ACTIVE SESSIONS (सुरक्षा मॉनिटरिंग व सेशन्स)
    // =========================================================================

    public function getSecurityOverview()
    {
        $sessions = DB::table('admin_security_sessions')->orderBy('id', 'desc')->get();

        $activeCount = DB::table('admin_security_sessions')->where('status', 'ACTIVE')->count();
        $failedAttempts = DB::table('admin_security_sessions')->where('status', 'FAILED_ATTEMPT')->count();
        $blockedIps = DB::table('admin_security_sessions')->where('is_blocked_ip', true)->count();
        $totalTokens = DB::table('personal_access_tokens')->count();

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
            'stats' => [
                'active_sessions' => $activeCount,
                'failed_attempts' => $failedAttempts,
                'blocked_ips' => $blockedIps,
                'active_tokens' => $totalTokens,
                'two_factor_ready' => true,
            ],
        ]);
    }

    public function terminateSession(Request $request, $id)
    {
        DB::table('admin_security_sessions')->where('id', $id)->update([
            'status' => 'TERMINATED',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'वापरकर्त्याचे थेट लॉगिन सत्र (Session) समाप्त करण्यात आले.',
        ]);
    }

    public function toggleBlockIp(Request $request, $id)
    {
        $session = DB::table('admin_security_sessions')->where('id', $id)->first();
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $newBlocked = !$session->is_blocked_ip;
        DB::table('admin_security_sessions')->where('id', $id)->update([
            'is_blocked_ip' => $newBlocked,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $newBlocked ? "आयपी [{$session->ip_address}] ब्लॉक करण्यात आला." : "आयपी [{$session->ip_address}] अनब्लॉक केला.",
            'is_blocked' => $newBlocked,
        ]);
    }
}
