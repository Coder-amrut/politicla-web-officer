<?php

use App\Http\Controllers\Admin\GeographyController as AdminGeographyController;
use App\Http\Controllers\Admin\GeographyTypeController as AdminGeographyTypeController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectGeographyLookupController;
use App\Http\Controllers\Admin\SurveyFormController;
use App\Http\Controllers\Admin\PublishedFormController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AdminAdvancedController;
use App\Http\Controllers\Admin\AdminChatbotController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\Neta\NetaDashboardController;
use App\Http\Controllers\Worker\WorkerDashboardController;
use App\Http\Controllers\Worker\KarkartaEvmController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Field Management Platform
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------------
// Public authentication endpoints
// ---------------------------------------------------------------------------

Route::prefix('auth')->group(function () {

    // POST /api/auth/register — max 10 attempts per minute per IP
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

    // POST /api/auth/login — max 5 attempts per minute per IP
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // POST /api/auth/dev-login — Quick developer login without password for rapid testing
    Route::post('/dev-login', [AuthController::class, 'devLogin']);

    // OTP endpoints for Worker login / registration
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // Authenticated-only auth endpoints
    Route::middleware('auth:sanctum')->group(function () {

        // POST /api/auth/logout
        Route::post('/logout', [AuthController::class, 'logout']);

        // GET /api/auth/me
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Authenticated Survey Form endpoints (Workers, Officers, Admins)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/survey-forms/templates', [SurveyFormController::class, 'getTemplates']);
    Route::get('/survey-forms/drafts', [SurveyFormController::class, 'getDrafts']);
    Route::post('/survey-forms/drafts', [SurveyFormController::class, 'saveDraft']);
    Route::delete('/survey-forms/drafts/{id}', [SurveyFormController::class, 'deleteDraft']);
    Route::get('/survey-forms/submissions', [SurveyFormController::class, 'getSubmissions']);
    Route::post('/survey-forms/submissions', [SurveyFormController::class, 'saveSubmission']);
});

// ---------------------------------------------------------------------------
// Read-only location master data for frontend selectors.
// These lookup routes are public so registration and other selection screens
// can load their options before the user has an authenticated session.
// ---------------------------------------------------------------------------
Route::prefix('master-data')->group(function () {
    // GET /api/master-data/states
    Route::get('/states', [MasterDataController::class, 'states']);

    // GET /api/master-data/districts
    Route::get('/districts', [MasterDataController::class, 'districts']);

    // GET /api/master-data/talukas?district_code=466
    Route::get('/talukas', [MasterDataController::class, 'talukas']);

    // GET /api/master-data/villages?district_code=466&taluka_code=4201
    Route::get('/villages', [MasterDataController::class, 'villages']);

    // GET /api/master-data/local-bodies?district_code=466&taluka_code=4201
    Route::get('/local-bodies', [MasterDataController::class, 'localBodies']);
});

// ---------------------------------------------------------------------------
// Admin-only endpoints (requires Sanctum token + ADMIN role)
// ---------------------------------------------------------------------------

Route::prefix('admin')
    ->middleware(['auth:sanctum', EnsureAdmin::class])
    ->group(function () {

        // GET  /api/admin/users/pending
        // IMPORTANT: this must come before the /{id} routes so Laravel
        // does not treat "pending" as an ID segment.
        Route::get('/users/pending', [AdminUserController::class, 'pending']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/permissions/{permissionId}/users', [RoleController::class, 'permissionUsers'])->whereNumber('permissionId');
        Route::put('/roles/{roleId}/permissions', [RoleController::class, 'syncPermissions']);
        Route::post('/roles/{roleId}/permissions/give', [RoleController::class, 'givePermission']);
        Route::delete('/roles/{roleId}/permissions/{permissionId}', [RoleController::class, 'removePermission']);
        Route::get('/users-list', [RoleController::class, 'usersList']);
        Route::get('/users/{userId}/permissions', [RoleController::class, 'getUserPermissions'])->whereNumber('userId');
        Route::put('/users/{userId}/permissions', [RoleController::class, 'syncUserPermissions'])->whereNumber('userId');
        Route::post('/users/{userId}/permissions/give', [RoleController::class, 'giveUserPermission'])->whereNumber('userId');
        Route::delete('/users/{userId}/permissions/{permissionId}', [RoleController::class, 'removeUserPermission'])->whereNumber('userId')->whereNumber('permissionId');
        Route::get('/project-geography/districts', [ProjectGeographyLookupController::class, 'districts']);
        Route::get('/project-geography/lok-sabha', [ProjectGeographyLookupController::class, 'lokSabha']);
        Route::get('/project-geography/lok-sabha/{lokSabhaNo}/assemblies', [ProjectGeographyLookupController::class, 'assembliesForLokSabha'])->whereNumber('lokSabhaNo');
        Route::get('/project-geography/lok-sabha/{lokSabhaNo}/districts', [ProjectGeographyLookupController::class, 'districtsForLokSabha'])->whereNumber('lokSabhaNo');
        Route::get('/project-geography/assemblies/{assemblyCode}/districts', [ProjectGeographyLookupController::class, 'districtsForAssembly'])->whereNumber('assemblyCode');
        Route::get('/project-geography/assemblies/{assemblyCode}/talukas', [ProjectGeographyLookupController::class, 'talukasForAssembly'])->whereNumber('assemblyCode');
        Route::get('/project-geography/assemblies', [ProjectGeographyLookupController::class, 'assemblies']);
        Route::get('/project-geography/graduate-constituencies', [ProjectGeographyLookupController::class, 'graduateConstituencies']);
        Route::get('/project-geography/graduate-constituencies/{code}/districts', [ProjectGeographyLookupController::class, 'districtsForGraduateConstituency']);
        Route::get('/project-geography/teacher-constituencies', [ProjectGeographyLookupController::class, 'teacherConstituencies']);
        Route::get('/project-geography/teacher-constituencies/{code}/districts', [ProjectGeographyLookupController::class, 'districtsForTeacherConstituency']);
        Route::get('/project-geography/polling-stations', [ProjectGeographyLookupController::class, 'pollingStations']);

        // ---------------------------------------------------------------------------
        // Survey Form Builder & Data Persistence (Saved in MySQL table `survey_form`)
        // ---------------------------------------------------------------------------
        Route::get('/survey-forms/templates', [SurveyFormController::class, 'getTemplates']);
        Route::put('/survey-forms/templates/{scope}', [SurveyFormController::class, 'saveTemplate']);
        Route::post('/survey-forms/templates/{scope}', [SurveyFormController::class, 'saveTemplate']);
        Route::post('/survey-forms/templates/{scope}/reset', [SurveyFormController::class, 'resetTemplate']);
        Route::get('/survey-forms/drafts', [SurveyFormController::class, 'getDrafts']);
        Route::post('/survey-forms/drafts', [SurveyFormController::class, 'saveDraft']);
        Route::delete('/survey-forms/drafts/{id}', [SurveyFormController::class, 'deleteDraft']);
        Route::get('/survey-forms/submissions', [SurveyFormController::class, 'getSubmissions']);
        Route::post('/survey-forms/submissions', [SurveyFormController::class, 'saveSubmission']);

        // ---------------------------------------------------------------------------
        // Published Forms & User Access Management (`published_forms` & `published_form_users`)
        // ---------------------------------------------------------------------------
        Route::get('/published-forms', [PublishedFormController::class, 'index']);
        Route::get('/published-forms/{uuid}', [PublishedFormController::class, 'show']);
        Route::post('/published-forms/publish', [PublishedFormController::class, 'publish']);
        Route::get('/published-forms/{uuid}/users', [PublishedFormController::class, 'getAssignableUsers']);
        Route::put('/published-forms/{uuid}/users', [PublishedFormController::class, 'assignUsers']);
        Route::post('/published-forms/{uuid}/users', [PublishedFormController::class, 'assignUsers']);
        Route::patch('/published-forms/{uuid}/status', [PublishedFormController::class, 'updateStatus']);
        Route::delete('/published-forms/{uuid}/users/{userId}', [PublishedFormController::class, 'removeUser'])->whereNumber('userId');
        Route::delete('/published-forms/{uuid}', [PublishedFormController::class, 'destroy']);

        // POST /api/admin/users/{id}/approve
        Route::post('/users/{id}/approve', [AdminUserController::class, 'approve']);

        // POST /api/admin/users/{id}/reject
        Route::post('/users/{id}/reject', [AdminUserController::class, 'reject']);

        // POST /api/admin/users  (admin-created Worker/Officer)
        Route::post('/users', [AdminUserController::class, 'store']);

        // ---------------------------------------------------------------------------
        // Project Management — Phase 2
        // ---------------------------------------------------------------------------

        // GET  /api/admin/projects              — list all projects (paginated)
        // POST /api/admin/projects              — create a new project
        Route::get('/projects',  [AdminProjectController::class, 'index']);
        Route::post('/projects', [AdminProjectController::class, 'store']);
        Route::get('/projects/{uuid}/members', [AdminProjectController::class, 'assignableMembers']);
        Route::put('/projects/{uuid}/members', [AdminProjectController::class, 'assignMembers']);
        Route::patch('/projects/{uuid}/members/{userId}/status', [AdminProjectController::class, 'updateMemberStatus'])->whereNumber('userId');
        Route::delete('/projects/{uuid}/members/{userId}', [AdminProjectController::class, 'removeMember'])->whereNumber('userId');

        // GET   /api/admin/projects/{uuid}         — get a single project
        // PATCH /api/admin/projects/{uuid}         — update metadata
        // PATCH /api/admin/projects/{uuid}/status  — transition status (workflow)
        Route::get('/projects/{uuid}',          [AdminProjectController::class, 'show']);
        Route::patch('/projects/{uuid}',        [AdminProjectController::class, 'update']);
        Route::patch('/projects/{uuid}/status', [AdminProjectController::class, 'updateStatus']);
        Route::delete('/projects/{uuid}',       [AdminProjectController::class, 'destroy']);

        // ---------------------------------------------------------------------------
        // Geography Management — Phase 3
        // ---------------------------------------------------------------------------

        // GET  /api/admin/geography-types       — list reference geography types (read-only)
        Route::get('/geography-types', [AdminGeographyTypeController::class, 'index']);

        // GET  /api/admin/projects/{projectUuid}/geographies                    — list geographies
        // POST /api/admin/projects/{projectUuid}/geographies                    — create geography
        Route::get('/projects/{projectUuid}/geographies',  [AdminGeographyController::class, 'index']);
        Route::post('/projects/{projectUuid}/geographies', [AdminGeographyController::class, 'store']);

        // GET   /api/admin/projects/{projectUuid}/geographies/{geographyUuid}        — get single geography
        // PATCH /api/admin/projects/{projectUuid}/geographies/{geographyUuid}        — update geography metadata
        // PATCH /api/admin/projects/{projectUuid}/geographies/{geographyUuid}/status — toggle status
        Route::get('/projects/{projectUuid}/geographies/{geographyUuid}',          [AdminGeographyController::class, 'show']);
        Route::patch('/projects/{projectUuid}/geographies/{geographyUuid}',        [AdminGeographyController::class, 'update']);
        Route::patch('/projects/{projectUuid}/geographies/{geographyUuid}/status', [AdminGeographyController::class, 'updateStatus']);

        // ---------------------------------------------------------------------------
        // Admin Advanced Modules (Approvals, Backup, Duplicates, Media, Security)
        // ---------------------------------------------------------------------------
        // 1. Data Approval & Correction
        Route::get('/submissions', [AdminAdvancedController::class, 'getSubmissions']);
        Route::post('/submissions/{id}/approve', [AdminAdvancedController::class, 'approveSubmission'])->whereNumber('id');
        Route::post('/submissions/{id}/reject', [AdminAdvancedController::class, 'rejectSubmission'])->whereNumber('id');
        Route::post('/submissions/{id}/correct', [AdminAdvancedController::class, 'correctSubmission'])->whereNumber('id');

        // 2. Database Backup & Restore
        Route::get('/system/backups', [AdminAdvancedController::class, 'getBackups']);
        Route::post('/system/backups', [AdminAdvancedController::class, 'createBackup']);
        Route::get('/system/backups/{id}/download', [AdminAdvancedController::class, 'downloadBackup'])->whereNumber('id');
        Route::post('/system/restore', [AdminAdvancedController::class, 'restoreBackup']);

        // 3. Duplicate Detection
        Route::get('/duplicates', [AdminAdvancedController::class, 'getDuplicateFlags']);
        Route::post('/duplicates/scan', [AdminAdvancedController::class, 'runDuplicateScan']);
        Route::post('/duplicates/{id}/resolve', [AdminAdvancedController::class, 'resolveDuplicate'])->whereNumber('id');

        // 4. Media & Document Management
        Route::get('/media-documents', [AdminAdvancedController::class, 'getMediaDocuments']);
        Route::post('/media-documents', [AdminAdvancedController::class, 'storeMediaDocument']);
        Route::get('/media-documents/{id}/raw', [AdminAdvancedController::class, 'rawMediaDocument'])->whereNumber('id');
        Route::get('/media-documents/{id}/download', [AdminAdvancedController::class, 'downloadMediaDocument'])->whereNumber('id');
        Route::delete('/media-documents/{id}', [AdminAdvancedController::class, 'deleteMediaDocument'])->whereNumber('id');

        // 5. Security Monitoring & Active Sessions
        Route::get('/security/overview', [AdminAdvancedController::class, 'getSecurityOverview']);
        Route::post('/security/sessions/{id}/terminate', [AdminAdvancedController::class, 'terminateSession'])->whereNumber('id');
        Route::post('/security/sessions/{id}/toggle-block', [AdminAdvancedController::class, 'toggleBlockIp'])->whereNumber('id');

        // 6. AI Database Chatbot Assistant
        Route::get('/chatbot/suggestions', [AdminChatbotController::class, 'getSuggestions']);
        Route::post('/chatbot/query', [AdminChatbotController::class, 'handleQuery']);

        // 7. User Attendance Management & Location Geofence Settings (Admin Command HQ)
        Route::get('/attendance', [AdminAttendanceController::class, 'getAttendance']);
        Route::get('/attendance/users/{userId}/history', [AdminAttendanceController::class, 'getUserEverydayAttendance'])->whereNumber('userId');
        Route::get('/attendance/location-setting', [AdminAttendanceController::class, 'getLocationSetting']);
        Route::put('/attendance/location-setting', [AdminAttendanceController::class, 'updateLocationSetting']);
        Route::post('/attendance/location-setting', [AdminAttendanceController::class, 'updateLocationSetting']);
        Route::post('/attendance/manual-mark', [AdminAttendanceController::class, 'manualMark']);
    });

// ---------------------------------------------------------------------------
// Neta / Officer Command App Endpoints (requires Sanctum token)
// ---------------------------------------------------------------------------
Route::prefix('neta')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/overview', [NetaDashboardController::class, 'overview']);
        Route::get('/booths', [NetaDashboardController::class, 'booths']);
        Route::get('/geography-filters', [NetaDashboardController::class, 'geographyFilters']);
        Route::get('/karyakartas', [NetaDashboardController::class, 'karyakartas']);
        Route::post('/karyakartas', [NetaDashboardController::class, 'storeKaryakarta']);
        Route::put('/karyakartas/{id}', [NetaDashboardController::class, 'updateKaryakarta'])->whereNumber('id');
        Route::post('/karyakartas/{id}/task', [NetaDashboardController::class, 'assignKaryakartaTask'])->whereNumber('id');
        Route::get('/roles', [NetaDashboardController::class, 'roles']);
        Route::get('/meetings', [NetaDashboardController::class, 'meetings']);
        Route::post('/meetings', [NetaDashboardController::class, 'storeMeeting']);
        Route::patch('/meetings/{id}/status', [NetaDashboardController::class, 'updateMeetingStatus'])->whereNumber('id');
        Route::get('/tasks', [NetaDashboardController::class, 'tasks']);
        Route::post('/tasks', [NetaDashboardController::class, 'storeTask']);
        Route::patch('/tasks/{id}/status', [NetaDashboardController::class, 'updateTaskStatus'])->whereNumber('id');
        Route::get('/reports', [NetaDashboardController::class, 'reports']);
        Route::get('/field-updates', [NetaDashboardController::class, 'fieldUpdates']);
        Route::post('/field-updates', [NetaDashboardController::class, 'storeFieldUpdate']);
        Route::get('/notifications', [NetaDashboardController::class, 'notifications']);
        Route::post('/notifications/broadcast', [NetaDashboardController::class, 'broadcastNotification']);
        Route::patch('/notifications/{id}/read', [NetaDashboardController::class, 'toggleNotificationRead'])->whereNumber('id');
        Route::post('/notifications/mark-all-read', [NetaDashboardController::class, 'markAllNotificationsRead']);
        Route::get('/export/{type}', [NetaDashboardController::class, 'exportData']);

        // CRM Citizen 360 Endpoints
        Route::get('/crm/citizens', [NetaDashboardController::class, 'crmCitizens']);
        Route::post('/crm/citizens', [NetaDashboardController::class, 'storeCrmCitizen']);
        Route::get('/crm/citizens/{id}', [NetaDashboardController::class, 'crmCitizenDetail'])->whereNumber('id');
        Route::post('/crm/citizens/{id}/interactions', [NetaDashboardController::class, 'storeCrmInteraction'])->whereNumber('id');
        Route::post('/crm/citizens/{id}/issues', [NetaDashboardController::class, 'storeCrmIssue'])->whereNumber('id');
        Route::patch('/crm/issues/{issueId}/status', [NetaDashboardController::class, 'updateCrmIssueStatus'])->whereNumber('issueId');
    });

// ---------------------------------------------------------------------------
// Worker / Karyakarta App Endpoints
// ---------------------------------------------------------------------------
Route::prefix('worker')->group(function () {
    Route::get('/overview', [WorkerDashboardController::class, 'overview']);
    Route::get('/profile', [WorkerDashboardController::class, 'getProfile']);
    Route::put('/profile', [WorkerDashboardController::class, 'updateProfile']);
    Route::get('/tasks', [WorkerDashboardController::class, 'getTasks']);
    Route::post('/tasks', [WorkerDashboardController::class, 'createTask']);
    Route::patch('/tasks/{id}/toggle', [WorkerDashboardController::class, 'toggleTask'])->whereNumber('id');
    Route::get('/follow-ups', [WorkerDashboardController::class, 'getFollowUps']);
    Route::post('/follow-ups', [WorkerDashboardController::class, 'createFollowUp']);
    Route::patch('/follow-ups/{id}/status', [WorkerDashboardController::class, 'updateFollowUpStatus'])->whereNumber('id');
    Route::get('/surveys', [WorkerDashboardController::class, 'getSurveys']);
    Route::post('/surveys/submit', [WorkerDashboardController::class, 'submitSurvey']);
    Route::get('/complaints', [WorkerDashboardController::class, 'getComplaints']);
    Route::post('/complaints', [WorkerDashboardController::class, 'storeComplaint']);
    Route::patch('/complaints/{id}/status', [WorkerDashboardController::class, 'updateComplaintStatus']);
    Route::get('/attendance', [WorkerDashboardController::class, 'getAttendance']);
    Route::get('/attendance-policy', [AdminAttendanceController::class, 'getLocationSetting']);
    Route::post('/attendance/punch-in', [WorkerDashboardController::class, 'punchIn']);
    Route::post('/attendance/punch-out', [WorkerDashboardController::class, 'punchOut']);
    Route::get('/contacts', [WorkerDashboardController::class, 'getContacts']);
    Route::post('/contacts/visit-log', [WorkerDashboardController::class, 'recordVisitLog']);
    Route::get('/check-duplicate', [WorkerDashboardController::class, 'checkDuplicate']);
    Route::get('/geography-hierarchy', [WorkerDashboardController::class, 'getGeographyHierarchy']);
    Route::get('/meetings', [WorkerDashboardController::class, 'getMeetings']);
    Route::post('/meetings', [WorkerDashboardController::class, 'scheduleMeeting']);
    Route::get('/candidates', [WorkerDashboardController::class, 'getCandidates']);
    Route::post('/vote', [WorkerDashboardController::class, 'castVote']);

    // EVM Demo & Voting System (karkarta_ tables)
    Route::prefix('evm')->group(function () {
        Route::get('/election', [KarkartaEvmController::class, 'getElectionData']);
        Route::post('/vote', [KarkartaEvmController::class, 'castVote']);
        Route::post('/candidate', [KarkartaEvmController::class, 'addCandidate']);
        Route::put('/candidate/{id}', [KarkartaEvmController::class, 'updateCandidate'])->whereNumber('id');
        Route::delete('/candidate/{id}', [KarkartaEvmController::class, 'deleteCandidate'])->whereNumber('id');
        Route::post('/reset-votes', [KarkartaEvmController::class, 'resetVotes']);
        Route::put('/settings', [KarkartaEvmController::class, 'updateSettings']);
        Route::put('/election', [KarkartaEvmController::class, 'updateElection']);
        Route::post('/start-election', [KarkartaEvmController::class, 'startElection']);
        Route::post('/end-election', [KarkartaEvmController::class, 'endElection']);
    });
});


