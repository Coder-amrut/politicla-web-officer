<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KarkartaEvmController extends Controller
{
    /**
     * Resolve effective worker user ID.
     */
    protected function getWorkerId(Request $request): int
    {
        $user = $request->user() ?: auth('sanctum')->user();
        if ($user) {
            return (int) $user->id;
        }

        if ($request->filled('user_id')) {
            return (int) $request->input('user_id');
        }

        $firstWorker = DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'WORKER')
            ->value('users.id');

        return $firstWorker ? (int) $firstWorker : 1;
    }

    /**
     * 1. Get Election Data, Candidates, Settings & Live Tally
     */
    public function getElectionData(Request $request)
    {
        $workerId = $this->getWorkerId($request);

        // Fetch latest election record
        $election = DB::table('karkarta_evm_elections')
            ->orderBy('id', 'desc')
            ->first();

        if (!$election) {
            $electionId = DB::table('karkarta_evm_elections')->insertGetId([
                'worker_id'              => $workerId,
                'name'                   => '२८२ - सांगली विधानसभा / नगर परिषद निवडणूक २०२६ (EVM Demo)',
                'message'                => 'घड्याळ चिन्हासमोरील क्रमांक २ बटण दाबून "सौ. वंदना चंद्रकांत परब" यांना प्रचंड मतांनी विजयी करा!',
                'date_time'              => 'मतदान दिनांक : २० नोव्हेंबर, २०२६ रोजी सकाळी ७:०० ते सायंकाळी ६:००',
                'whatsapp_number'        => '919822011231',
                'status'                 => 'ACTIVE',
                'start_time'             => now(),
                'end_time'               => now()->addHours(8),
                'timer_duration_seconds' => 28800,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
            $election = DB::table('karkarta_evm_elections')->where('id', $electionId)->first();
        } else {
            // Auto-stop election if timer has expired
            if ($election->status === 'ACTIVE' && !empty($election->end_time)) {
                $endAt = \Carbon\Carbon::parse($election->end_time);
                if (now()->greaterThanOrEqualTo($endAt)) {
                    DB::table('karkarta_evm_elections')
                        ->where('id', $election->id)
                        ->update([
                            'status'     => 'ENDED',
                            'updated_at' => now(),
                        ]);
                    $election = DB::table('karkarta_evm_elections')->where('id', $election->id)->first();
                }
            }
        }

        if ($election) {
            $election->start_time_iso = !empty($election->start_time) ? \Carbon\Carbon::parse($election->start_time)->toIso8601String() : null;
            $election->end_time_iso = !empty($election->end_time) ? \Carbon\Carbon::parse($election->end_time)->toIso8601String() : null;
            $election->remaining_seconds = ($election->status === 'ACTIVE' && !empty($election->end_time))
                ? max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($election->end_time), false))
                : 0;
        }

        $candidates = DB::table('karkarta_evm_candidates')
            ->where('election_id', $election->id)
            ->orderBy('serial_number', 'asc')
            ->get();

        $settings = DB::table('karkarta_evm_settings')
            ->where('worker_id', $workerId)
            ->first();

        if (!$settings) {
            $settings = DB::table('karkarta_evm_settings')->first();
        }

        $totalVotes = (int) DB::table('karkarta_evm_candidates')
            ->where('election_id', $election->id)
            ->sum('votes');

        // Check if this worker has already voted in this active election
        $workerVote = DB::table('karkarta_evm_votes')
            ->where('election_id', $election->id)
            ->where('worker_id', $workerId)
            ->latest('id')
            ->first();

        $hasVoted = (bool) $workerVote;

        $workerVotes = DB::table('karkarta_evm_votes')
            ->where('election_id', $election->id)
            ->where('worker_id', $workerId)
            ->get();

        // All votes log for Admin (who gave vote, candidate chosen, booth, time)
        $allVotes = DB::table('karkarta_evm_votes')
            ->leftJoin('users', 'karkarta_evm_votes.worker_id', '=', 'users.id')
            ->where('karkarta_evm_votes.election_id', $election->id)
            ->select(
                'karkarta_evm_votes.*',
                'users.full_name as worker_full_name',
                'users.email as worker_email'
            )
            ->orderBy('karkarta_evm_votes.id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'election'    => $election,
                'candidates'  => $candidates,
                'settings'    => $settings ?: [
                    'enable_sound'         => true,
                    'enable_vibration'     => true,
                    'allow_multiple_votes' => false,
                    'contact_whatsapp'     => '9822011231',
                    'contact_call'         => '9822011231',
                ],
                'totalVotes'  => $totalVotes,
                'hasVoted'    => $hasVoted,
                'workerVote'  => $workerVote,
                'workerVotes' => $workerVotes,
                'allVotes'    => $allVotes,
                'serverTime'  => now()->toISOString(),
            ],
        ]);
    }

    /**
     * 2. Cast EVM Vote
     */
    public function castVote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidateId'   => 'required|integer',
            'candidateName' => 'required|string',
            'electionId'    => 'nullable|integer',
            'voterName'     => 'nullable|string',
            'voterPhone'    => 'nullable|string',
            'boothNo'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $workerId = $this->getWorkerId($request);
        $candidateId = (int) $request->candidateId;

        $candidate = DB::table('karkarta_evm_candidates')->where('id', $candidateId)->first();
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'उमेदवार आढळला नाही.'], 404);
        }

        $election = DB::table('karkarta_evm_elections')->where('id', $candidate->election_id)->first();
        if (!$election || $election->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'निवडणूक सध्या बंद आहे. प्रशासनाने (Admin) निवडणूक सुरू केल्यावरच मतदान करता येईल.'
            ], 403);
        }

        // Check if countdown timer has expired
        if (!empty($election->end_time)) {
            $endAt = \Carbon\Carbon::parse($election->end_time);
            if (now()->greaterThanOrEqualTo($endAt)) {
                DB::table('karkarta_evm_elections')->where('id', $election->id)->update([
                    'status'     => 'ENDED',
                    'updated_at' => now(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'निवडणुकीची निर्धारित वेळ संपली आहे. मतदान प्रक्रिया बंद झाली आहे.'
                ], 403);
            }
        }

        // ONE VOTE PER WORKER ENFORCEMENT
        $alreadyVoted = DB::table('karkarta_evm_votes')
            ->where('election_id', $candidate->election_id)
            ->where('worker_id', $workerId)
            ->first();

        if ($alreadyVoted) {
            return response()->json([
                'success'      => false,
                'alreadyVoted' => true,
                'message'      => 'आपण या निवडणुकीसाठी आधीच मत दिलेले आहे (Vote Already Cast)! नियमानुसार एका निवडणुकीत एकाच वेळी मतदान करता येते.'
            ], 400);
        }

        $electionId = $candidate->election_id;

        // Fetch current user name if available for audit trail
        $user = $request->user() ?: auth('sanctum')->user();
        $workerName = $user ? $user->full_name : 'क्षेत्र कार्यकर्ता';

        // Insert vote record
        $voteId = DB::table('karkarta_evm_votes')->insertGetId([
            'election_id'    => $electionId,
            'candidate_id'   => $candidateId,
            'worker_id'      => $workerId,
            'candidate_name' => $candidate->candidate_name,
            'voter_name'     => $request->voterName ?: $workerName,
            'voter_phone'    => $request->voterPhone ?: ($user ? $user->phone : null),
            'booth_no'       => $request->boothNo ?: 'सांगली मध्य बूथ क्र. १',
            'voted_at'       => now(),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Increment candidate votes
        DB::table('karkarta_evm_candidates')
            ->where('id', $candidateId)
            ->increment('votes');

        // Log worker activity
        DB::table('worker_activities')->insert([
            'worker_id'  => $workerId,
            'type'       => 'clipboard',
            'title'      => "EVM मतदान: {$candidate->candidate_name} ({$candidate->party_name})",
            'time'       => 'आत्ताच (Just now)',
            'bg_color'   => '#eff6ff',
            'color'      => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updatedCandidate = DB::table('karkarta_evm_candidates')->where('id', $candidateId)->first();
        $totalVotes = (int) DB::table('karkarta_evm_candidates')->where('election_id', $electionId)->sum('votes');

        return response()->json([
            'success'   => true,
            'message'   => "आपले मत '{$candidate->candidate_name}' यांना यशस्वीरीत्या नोंदवले गेले!",
            'data'      => [
                'voteId'     => $voteId,
                'candidate'  => $updatedCandidate,
                'totalVotes' => $totalVotes,
                'hasVoted'   => true,
            ],
        ]);
    }

    /**
     * 3. Add New Candidate to EVM Ballot
     */
    public function addCandidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_name'  => 'required|string|max:150',
            'party_name'      => 'required|string|max:150',
            'serial_number'   => 'nullable|integer',
            'candidate_photo' => 'nullable|string',
            'party_symbol'    => 'nullable|string',
            'position'        => 'nullable|string',
            'section_color'   => 'nullable|string',
            'highlighted'     => 'nullable|boolean',
            'voice_message'   => 'nullable|string',
            'audio_url'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $election = DB::table('karkarta_evm_elections')->where('status', 'ACTIVE')->first();
        $electionId = $election ? $election->id : 1;

        $nextSerial = $request->serial_number;
        if (!$nextSerial) {
            $maxSerial = DB::table('karkarta_evm_candidates')->where('election_id', $electionId)->max('serial_number');
            $nextSerial = ($maxSerial ?: 0) + 1;
        }

        $slug = Str::slug($request->candidate_name);
        if (empty($slug)) {
            $slug = 'candidate-' . $nextSerial;
        }

        $id = DB::table('karkarta_evm_candidates')->insertGetId([
            'election_id'      => $electionId,
            'serial_number'    => $nextSerial,
            'candidate_name'   => $request->candidate_name,
            'party_name'       => $request->party_name,
            'candidate_photo'  => $request->candidate_photo,
            'party_symbol'     => $request->party_symbol,
            'position'         => $request->position ?: 'विधानसभा / प्रभाग',
            'section_color'    => $request->section_color ?: '#ea580c',
            'highlighted'      => (bool) $request->highlighted,
            'voice_message'    => $request->voice_message,
            'audio_url'        => $request->audio_url,
            'slug'             => $slug,
            'votes'            => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $created = DB::table('karkarta_evm_candidates')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'उमेदवार बॅलेटवर यशस्वीरीत्या जोडला!',
            'data'    => $created,
        ]);
    }

    /**
     * 4. Update Candidate
     */
    public function updateCandidate(Request $request, int $id)
    {
        $candidate = DB::table('karkarta_evm_candidates')->where('id', $id)->first();
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'उमेदवार आढळला नाही.'], 404);
        }

        $updateData = [];
        if ($request->has('candidate_name')) $updateData['candidate_name'] = $request->candidate_name;
        if ($request->has('party_name')) $updateData['party_name'] = $request->party_name;
        if ($request->has('serial_number')) $updateData['serial_number'] = (int) $request->serial_number;
        if ($request->has('candidate_photo')) $updateData['candidate_photo'] = $request->candidate_photo;
        if ($request->has('party_symbol')) $updateData['party_symbol'] = $request->party_symbol;
        if ($request->has('position')) $updateData['position'] = $request->position;
        if ($request->has('section_color')) $updateData['section_color'] = $request->section_color;
        if ($request->has('highlighted')) $updateData['highlighted'] = (bool) $request->highlighted;
        if ($request->has('voice_message')) $updateData['voice_message'] = $request->voice_message;
        if ($request->has('audio_url')) $updateData['audio_url'] = $request->audio_url;
        $updateData['updated_at'] = now();

        DB::table('karkarta_evm_candidates')->where('id', $id)->update($updateData);

        $updated = DB::table('karkarta_evm_candidates')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'उमेदवाराची माहिती अद्यतनित झाली!',
            'data'    => $updated,
        ]);
    }

    /**
     * 5. Delete Candidate
     */
    public function deleteCandidate(int $id)
    {
        DB::table('karkarta_evm_candidates')->where('id', $id)->delete();
        DB::table('karkarta_evm_votes')->where('candidate_id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'उमेदवार हटवला गेला!',
        ]);
    }

    /**
     * 6. Reset EVM Votes for fresh session
     */
    public function resetVotes(Request $request)
    {
        $workerId = $this->getWorkerId($request);

        $election = DB::table('karkarta_evm_elections')->where('status', 'ACTIVE')->first();
        $electionId = $election ? $election->id : 1;

        DB::table('karkarta_evm_candidates')
            ->where('election_id', $electionId)
            ->update(['votes' => 0, 'updated_at' => now()]);

        DB::table('karkarta_evm_votes')
            ->where('election_id', $electionId)
            ->delete();

        DB::table('worker_activities')->insert([
            'worker_id'  => $workerId,
            'type'       => 'clock',
            'title'      => 'EVM मतदानाचे आकडे रीसेट केले (नवीन सत्र)',
            'time'       => 'आत्ताच (Just now)',
            'bg_color'   => '#fff7ed',
            'color'      => '#ea580c',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'डेमो मतदान आकडे रीसेट झाले! नवीन मतदान सत्र सुरू झाले.',
        ]);
    }

    /**
     * 7. Update Settings
     */
    public function updateSettings(Request $request)
    {
        $workerId = $this->getWorkerId($request);

        $settings = DB::table('karkarta_evm_settings')->where('worker_id', $workerId)->first();

        $data = [
            'enable_sound'         => $request->has('enable_sound') ? (bool) $request->enable_sound : true,
            'enable_vibration'     => $request->has('enable_vibration') ? (bool) $request->enable_vibration : true,
            'allow_multiple_votes' => $request->has('allow_multiple_votes') ? (bool) $request->allow_multiple_votes : true,
            'contact_whatsapp'     => $request->contact_whatsapp,
            'contact_call'         => $request->contact_call,
            'updated_at'           => now(),
        ];

        if ($settings) {
            DB::table('karkarta_evm_settings')->where('worker_id', $workerId)->update($data);
        } else {
            $data['worker_id'] = $workerId;
            $data['created_at'] = now();
            DB::table('karkarta_evm_settings')->insert($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'EVM सेटिंग्ज जतन झाल्या!',
        ]);
    }

    /**
     * 8. Update Election Details
     */
    public function updateElection(Request $request)
    {
        $election = DB::table('karkarta_evm_elections')->orderBy('id', 'desc')->first();
        if (!$election) {
            return response()->json(['success' => false, 'message' => 'निवडणूक आढळली नाही.'], 404);
        }

        $data = [];
        if ($request->has('name')) $data['name'] = $request->name;
        if ($request->has('message')) $data['message'] = $request->message;
        if ($request->has('date_time')) $data['date_time'] = $request->date_time;
        if ($request->has('whatsapp_number')) $data['whatsapp_number'] = $request->whatsapp_number;
        if ($request->has('status')) $data['status'] = $request->status;
        $data['updated_at'] = now();

        DB::table('karkarta_evm_elections')->where('id', $election->id)->update($data);

        $updated = DB::table('karkarta_evm_elections')->where('id', $election->id)->first();

        return response()->json([
            'success' => true,
            'message' => 'निवडणुकीची माहिती जतन झाली!',
            'data'    => $updated,
        ]);
    }

    /**
     * 9. Start Election with Custom Countdown Timer (Day / Hr / Min / Sec)
     */
    public function startElection(Request $request)
    {
        $election = DB::table('karkarta_evm_elections')->orderBy('id', 'desc')->first();
        if (!$election) {
            $electionId = DB::table('karkarta_evm_elections')->insertGetId([
                'name'                   => '२८२ - सांगली विधानसभा / नगर परिषद निवडणूक २०२६',
                'status'                 => 'ACTIVE',
                'start_time'             => now(),
                'timer_duration_seconds' => 3600,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
            $election = DB::table('karkarta_evm_elections')->where('id', $electionId)->first();
        }

        $days    = (int) $request->input('days', 0);
        $hours   = (int) $request->input('hours', 0);
        $minutes = (int) $request->input('minutes', 0);
        $seconds = (int) $request->input('seconds', 0);

        $totalSeconds = ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;
        if ($totalSeconds <= 0 && $request->filled('total_seconds')) {
            $totalSeconds = (int) $request->total_seconds;
        }
        if ($totalSeconds <= 0) {
            // Default 4 hours if none provided
            $totalSeconds = 14400;
        }

        $startTime = now();
        $endTime = now()->addSeconds($totalSeconds);

        // Reset previous votes for this election when starting a fresh official round
        $resetVotes = $request->boolean('reset_votes', true);
        if ($resetVotes) {
            DB::table('karkarta_evm_votes')->where('election_id', $election->id)->delete();
            DB::table('karkarta_evm_candidates')->where('election_id', $election->id)->update(['votes' => 0]);
        }

        $updateData = [
            'status'                 => 'ACTIVE',
            'start_time'             => $startTime,
            'end_time'               => $endTime,
            'timer_duration_seconds' => $totalSeconds,
            'updated_at'             => now(),
        ];

        if ($request->filled('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->filled('message')) {
            $updateData['message'] = $request->message;
        }

        DB::table('karkarta_evm_elections')->where('id', $election->id)->update($updateData);

        $updated = DB::table('karkarta_evm_elections')->where('id', $election->id)->first();
        if ($updated) {
            $updated->start_time_iso = !empty($updated->start_time) ? \Carbon\Carbon::parse($updated->start_time)->toIso8601String() : null;
            $updated->end_time_iso = !empty($updated->end_time) ? \Carbon\Carbon::parse($updated->end_time)->toIso8601String() : null;
            $updated->remaining_seconds = max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($updated->end_time), false));
        }

        return response()->json([
            'success' => true,
            'message' => 'निवडणूक अधिकृतपणे सुरू झाली आहे! मतदान टाइमर सक्रिय झाला.',
            'data'    => $updated,
        ]);
    }

    /**
     * 10. End / Stop Election
     */
    public function endElection(Request $request)
    {
        $election = DB::table('karkarta_evm_elections')->orderBy('id', 'desc')->first();
        if (!$election) {
            return response()->json(['success' => false, 'message' => 'निवडणूक आढळली नाही.'], 404);
        }

        DB::table('karkarta_evm_elections')->where('id', $election->id)->update([
            'status'     => 'ENDED',
            'end_time'   => now(),
            'updated_at' => now(),
        ]);

        $updated = DB::table('karkarta_evm_elections')->where('id', $election->id)->first();
        if ($updated) {
            $updated->start_time_iso = !empty($updated->start_time) ? \Carbon\Carbon::parse($updated->start_time)->toIso8601String() : null;
            $updated->end_time_iso = !empty($updated->end_time) ? \Carbon\Carbon::parse($updated->end_time)->toIso8601String() : null;
            $updated->remaining_seconds = 0;
        }

        return response()->json([
            'success' => true,
            'message' => 'निवडणूक समाप्त करण्यात आली आहे. सर्व मतदान प्रक्रिया बंद झाली.',
            'data'    => $updated,
        ]);
    }
}
