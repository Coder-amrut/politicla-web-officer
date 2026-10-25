<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class AdminChatbotController extends Controller
{
    /**
     * Get categorized quick-prompt suggestion chips.
     */
    public function getSuggestions(Request $request)
    {
        $isMr = $request->query('lang', 'mr') === 'mr';

        $suggestions = [
            [
                'id' => 'village_demographics',
                'category' => $isMr ? 'गाव लोकसंख्या' : 'Villages',
                'label' => $isMr ? '🏘️ गावानुसार लोकसंख्या व मतदार' : '🏘️ Village Population & Voters',
                'query' => $isMr ? 'गावानुसार एकूण लोकसंख्या आणि मतदार संख्या दाखवा' : 'Show village population and total voters summary',
            ],
            [
                'id' => 'voters_summary',
                'category' => $isMr ? 'मतदार सारांश' : 'Voters',
                'label' => $isMr ? '📊 एकूण मतदार व राजकीय कल सारांश' : '📊 Total Voters & Sentiment Summary',
                'query' => $isMr ? 'एकूण मतदार संख्या आणि राजकीय कल दाखवा' : 'Show total voters and political sentiment summary',
            ],
            [
                'id' => 'booth_status',
                'category' => $isMr ? 'बूथ कामगिरी' : 'Booths',
                'label' => $isMr ? '🗳️ बूथनिहाय प्रगती व मतदार कव्हरेज' : '🗳️ Booth Progress & Coverage',
                'query' => $isMr ? 'बूथनिहाय प्रगती आणि आघाडीवरील बूथ दाखवा' : 'Show booth progress and top covered booths',
            ],
            [
                'id' => 'civic_issues',
                'category' => $isMr ? 'नागरी समस्या' : 'Issues',
                'label' => $isMr ? '⚠️ प्रलंबित नागरी समस्या व तक्रारी' : '⚠️ Pending Civic Grievances',
                'query' => $isMr ? 'सध्याच्या प्रलंबित नागरी समस्या कोणत्या आहेत?' : 'What are the current pending civic issues?',
            ],
            [
                'id' => 'karyakarta_list',
                'category' => $isMr ? 'कार्यकर्ते' : 'Karyakartas',
                'label' => $isMr ? '👥 सक्रिय कार्यकर्ते व सर्व्हे कामगिरी' : '👥 Active Karyakartas & Performance',
                'query' => $isMr ? 'सक्रिय कार्यकर्ते आणि त्यांची कामगिरी दाखवा' : 'Show active field workers and survey performance',
            ],
            [
                'id' => 'pending_approvals',
                'category' => $isMr ? 'मंजुरी कक्ष' : 'Approvals',
                'label' => $isMr ? '📋 प्रलंबित सर्व्हे नोंदणी मंजुरी' : '📋 Pending Field Survey Submissions',
                'query' => $isMr ? 'तपासणीसाठी प्रलंबित सर्व्हे नोंदी किती आहेत?' : 'How many survey submissions are pending approval?',
            ],
            [
                'id' => 'official_docs',
                'category' => $isMr ? 'दस्तऐवज' : 'Documents',
                'label' => $isMr ? '📁 अधिकृत शासकीय दस्तऐवज भांडार' : '📁 Official Document Repository',
                'query' => $isMr ? 'भांडारात कोणते शासकीय दस्तऐवज उपलब्ध आहेत?' : 'What official documents are stored in repository?',
            ],
            [
                'id' => 'scheme_benefits',
                'category' => $isMr ? 'योजना' : 'Schemes',
                'label' => $isMr ? '🏛️ शासकीय योजना व लाडकी बहीण लाभार्थी' : '🏛️ Government Scheme Beneficiaries',
                'query' => $isMr ? 'शासकीय योजनांचे लाभार्थी आणि लाडकी बहीण योजनेची माहिती द्या' : 'Show government scheme beneficiaries data',
            ],
        ];

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Handle user chat query and return grounded database response.
     */
    public function handleQuery(Request $request)
    {
        try {
            $message = trim((string)($request->input('message') ?? $request->json('message') ?? ''));
            $lang = $request->input('lang', 'mr');
            $isMr = $lang === 'mr';

            if (empty($message)) {
                return response()->json([
                    'success' => false,
                    'message' => $isMr ? 'कृपया प्रश्न विचारा.' : 'Please provide a query message.',
                ], 422);
            }

            // 1. Gather live database facts and detect intent
            $contextData = $this->gatherDatabaseContext($message, $isMr);

            // 1. GREETING check - instant friendly response
            if ($contextData['intent'] === 'GREETING') {
                return response()->json([
                    'success' => true,
                    'reply' => $this->formatSmartResponse($message, $contextData, $isMr),
                    'intent' => 'GREETING',
                    'related_data' => [],
                    'suggested_actions' => $contextData['suggested_actions'] ?? [],
                    'source' => 'quick_greeting',
                ]);
            }

            // 2. Specific NOT FOUND records in DB - immediate honest notice
            if (in_array($contextData['intent'], ['NOT_FOUND_CITIZEN', 'NOT_FOUND_BOOTH'])) {
                return response()->json([
                    'success' => true,
                    'reply' => $this->formatSmartResponse($message, $contextData, $isMr),
                    'intent' => $contextData['intent'],
                    'related_data' => [],
                    'suggested_actions' => $this->getDefaultSuggestions($isMr),
                    'source' => 'db_record_not_found',
                ]);
            }

            $hasDbMatch = in_array($contextData['intent'], [
                'CITIZEN_LOOKUP', 'BOOTH_INFO', 'CIVIC_ISSUES', 'KARYAKARTAS',
                'SUBMISSIONS_APPROVAL', 'OFFICIAL_DOCUMENTS', 'SCHEMES_INFO', 'GENERAL_OVERVIEW',
                'SPECIFIC_VILLAGE_DEMOGRAPHICS', 'ALL_VILLAGES_DEMOGRAPHICS',
            ]);

            // 3. Query Gemini:
            // - 1st check on DB: Grounded in verified DB facts.
            // - Then check other side: General campaign strategy, slogans, advice, speeches.
            $apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');
            if (!empty($apiKey)) {
                $llmResponse = $this->queryGeminiApi($message, $contextData, $isMr, $hasDbMatch);
                if (!empty($llmResponse)) {
                    return response()->json([
                        'success' => true,
                        'reply' => trim($llmResponse),
                        'intent' => $contextData['intent'],
                        'related_data' => $contextData['structured_items'] ?? [],
                        'suggested_actions' => $contextData['suggested_actions'] ?? [],
                        'source' => $hasDbMatch ? 'gemini_db_grounded' : 'gemini_other_side',
                    ]);
                }
            }

            // 4. Built-in Smart Query Engine Response (Grounding & Fallback)
            $smartReply = $this->formatSmartResponse($message, $contextData, $isMr);

            return response()->json([
                'success' => true,
                'reply' => $smartReply,
                'intent' => $contextData['intent'],
                'related_data' => $contextData['structured_items'] ?? [],
                'suggested_actions' => $contextData['suggested_actions'] ?? [],
                'source' => $hasDbMatch ? 'mysql_live_database' : 'system_fallback',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Chatbot Controller Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $isMr = ($request->input('lang', 'mr') === 'mr');
            $safeContext = [
                'intent' => 'GENERAL_OVERVIEW',
                'data' => [],
                'structured_items' => [],
                'suggested_actions' => $this->getDefaultSuggestions($isMr),
            ];

            return response()->json([
                'success' => true,
                'reply' => $this->formatSmartResponse($request->input('message', ''), $safeContext, $isMr),
                'intent' => 'GENERAL_OVERVIEW',
                'related_data' => [],
                'suggested_actions' => $this->getDefaultSuggestions($isMr),
                'source' => 'system_recovery',
            ]);
        }
    }

    /**
     * Default suggestion chips for general navigation and fallback.
     */
    private function getDefaultSuggestions(bool $isMr): array
    {
        return $isMr ? [
            ['label' => '🏘️ गाव लोकसंख्या व मतदार', 'query' => 'गावानुसार एकूण लोकसंख्या आणि मतदार संख्या दाखवा'],
            ['label' => '📊 एकूण मतदार सारांश', 'query' => 'एकूण मतदार संख्या आणि राजकीय कल दाखवा'],
            ['label' => '🗳️ बूथनिहाय प्रगती', 'query' => 'बूथनिहाय प्रगती आणि आघाडीवरील बूथ दाखवा'],
            ['label' => '⚠️ प्रलंबित नागरी समस्या', 'query' => 'सध्याच्या प्रलंबित नागरी समस्या कोणत्या आहेत?'],
            ['label' => '👥 सक्रिय कार्यकर्ते', 'query' => 'सक्रिय कार्यकर्ते आणि त्यांची कामगिरी दाखवा'],
            ['label' => '📋 प्रलंबित सर्व्हे मंजुरी', 'query' => 'तपासणीसाठी प्रलंबित सर्व्हे नोंदी किती आहेत?'],
            ['label' => '📢 प्रचार घोषणा', 'query' => 'प्रभावी प्रचार घोषणा सांगा'],
        ] : [
            ['label' => '🏘️ Village Population & Voters', 'query' => 'Show village population and total voters summary'],
            ['label' => '📊 Total Voters Summary', 'query' => 'Show total voters and political sentiment summary'],
            ['label' => '🗳️ Booth Progress', 'query' => 'Show booth progress and top covered booths'],
            ['label' => '⚠️ Pending Issues', 'query' => 'What are the current pending civic issues?'],
            ['label' => '👥 Active Workers', 'query' => 'Show active field workers and survey performance'],
            ['label' => '📋 Pending Approvals', 'query' => 'How many survey submissions are pending approval?'],
            ['label' => '📢 Campaign Slogans', 'query' => 'Give effective campaign slogans'],
        ];
    }

    /**
     * Parse intent and query real MySQL tables.
     */
    private function gatherDatabaseContext(string $message, bool $isMr): array
    {
        $lower = mb_strtolower($message);
        $context = [
            'intent' => 'UNKNOWN_QUERY',
            'structured_items' => [],
            'suggested_actions' => $this->getDefaultSuggestions($isMr),
            'data' => [],
        ];

        // 1. Greetings check
        $greetings = [
            'hi', 'hello', 'hey', 'namaskar', 'namaste', 'नमस्कार', 'नमस्ते', 'सुप्रभात',
            'गुड मॉर्निंग', 'good morning', 'good afternoon', 'good evening',
            'राम राम', 'जय महाराष्ट्र', 'हाय', 'हॅलो', 'सस्नेह नमस्कार', 'प्रणाम'
        ];
        $trimmedMsg = trim($lower, "!?. \t\n\r\0\x0B");
        if (in_array($trimmedMsg, $greetings) || in_array($lower, $greetings)) {
            $context['intent'] = 'GREETING';
            return $context;
        }

        // 1.2 Help / Guide query
        $isHelpQuery = preg_match('/(?:help|मदत|कशी मदत|काय विचारू|काय करू शकता|मेनू|menu|मार्गदर्शन|options|पर्याय)/iu', $message);
        if ($isHelpQuery) {
            $context['intent'] = 'HELP_GUIDE';
            $context['suggested_actions'] = $this->getDefaultSuggestions($isMr);
            return $context;
        }

        // 1.3 Election Model Code of Conduct (आचारसंहिता)
        $isMccQuery = preg_match('/(?:आचारसंहिता|code of conduct|निवडणूक नियम|ध्वनिक्षेपक|परवानगी|सभा नियम|रॅली नियम)/iu', $message);
        if ($isMccQuery) {
            $context['intent'] = 'CODE_OF_CONDUCT';
            $context['suggested_actions'] = [
                ['label' => $isMr ? '📢 प्रचार घोषणा' : 'Campaign Slogans', 'query' => $isMr ? 'प्रभावी प्रचार घोषणा सांगा' : 'Give effective campaign slogans'],
                ['label' => $isMr ? '🎤 भाषण मुद्दे' : 'Speech Points', 'query' => $isMr ? 'सभेसाठी भाषणाचे मुद्दे सांगा' : 'Give rally speech points'],
            ];
            return $context;
        }

        // 1.5 Check for Campaign Strategy / Slogans / Speech / Creative / Tips queries first ("Other side")
        $isCampaignStrategyQuery = preg_match('/(?:घोषणा|slogan|भाषण|speech|सल्ला|advice|टिप्स|tips|नियोजन|planning|रणनीती|strategy|कसे करावे|कसे बोलावे|काय बोलावे|सोशल मीडिया|social media|पोस्ट|post|कॅम्पेन|campaign idea|प्रचार आयडिया)/iu', $message);
        if ($isCampaignStrategyQuery) {
            $context['intent'] = 'CAMPAIGN_STRATEGY';
            $context['suggested_actions'] = [
                ['label' => $isMr ? '📢 प्रचार घोषणा' : 'Campaign Slogans', 'query' => $isMr ? 'प्रभावी प्रचार घोषणा सांगा' : 'Give effective campaign slogans'],
                ['label' => $isMr ? '🎤 भाषण मुद्दे' : 'Speech Points', 'query' => $isMr ? 'सभेसाठी भाषणाचे मुद्दे सांगा' : 'Give rally speech points'],
                ['label' => $isMr ? '📊 मतदार सारांश' : 'Voters Summary', 'query' => $isMr ? 'एकूण मतदार सारांश दाखवा' : 'Show total voters summary'],
            ];
            return $context;
        }

        // 1.8 Village Population & Voters Lookup (e.g. "माधवनगर ची लोकसंख्या आणि मतदार", "गाव लोकसंख्या", "गावातील मतदार", "बुधगाव मधील मतदार आणि लोकसंख्या")
        $isVillageQuery = preg_match('/(?:लोकसंख्या|population|पॉप्युलेशन|वस्ती|गाव|गावे|गावाची|गावातील|गावांची|गावनिहाय|village|villages|ग्रामपंचायत|प्रभाग|नगरपरिषद)/iu', $message);

        $knownVillages = [
            'माधवनगर' => 'Madhavnagar',
            'बुधगाव' => 'Budhgaon',
            'बामणी' => 'Bamani',
            'कुपवाड' => 'Kupwad',
            'वसंत कॉलनी' => 'Vasant Colony',
            'गावभाग' => 'Gavbhag',
            'गणपती पेठ' => 'Ganpati Peth',
            'विश्रामबाग' => 'Vishrambag',
            'संजयनगर' => 'Sanjaynagar',
            'मिरज' => 'Miraj',
            'इस्लामपूर' => 'Islampur',
            'उरुण' => 'Urun',
            'तासगाव' => 'Tasgaon',
            'राजारामपुरी' => 'Rajarampuri',
            'उंब्रज' => 'Umbraj',
        ];

        $matchedVillageKeyword = null;
        foreach ($knownVillages as $mrName => $enName) {
            if (mb_stripos($message, $mrName) !== false || stripos($message, $enName) !== false) {
                $matchedVillageKeyword = $mrName;
                break;
            }
        }

        // If specific village mentioned or village question with village name
        if ($matchedVillageKeyword) {
            $villageBooths = DB::table('neta_booth_progress')
                ->where(function($q) use ($matchedVillageKeyword) {
                    $q->where('village_mr', 'like', "%{$matchedVillageKeyword}%")
                      ->orWhere('village_en', 'like', "%{$matchedVillageKeyword}%")
                      ->orWhere('booth_name_mr', 'like', "%{$matchedVillageKeyword}%");
                })
                ->get();

            if ($villageBooths->isNotEmpty()) {
                $context['intent'] = 'SPECIFIC_VILLAGE_DEMOGRAPHICS';
                $totalPop = $villageBooths->sum('total_population');
                $totalVoters = $villageBooths->sum('total_voters');
                $totalSurveyed = $villageBooths->sum('surveyed_voters');
                $ratio = $totalPop > 0 ? round(($totalVoters / $totalPop) * 100, 1) : 0;
                $coverage = $totalVoters > 0 ? round(($totalSurveyed / $totalVoters) * 100, 1) : 0;
                $boothNos = $villageBooths->pluck('booth_no')->implode(', ');

                $context['data'] = [
                    'village_name' => $matchedVillageKeyword,
                    'full_village_name' => $villageBooths->count() > 1 ? "{$matchedVillageKeyword} (एकूण {$villageBooths->count()} प्रभाग/बूथ)" : $villageBooths[0]->village_mr,
                    'taluka' => $villageBooths[0]->taluka_mr,
                    'district' => $villageBooths[0]->district_mr,
                    'assembly' => $villageBooths[0]->assembly_mr,
                    'total_population' => $totalPop,
                    'total_voters' => $totalVoters,
                    'surveyed_voters' => $totalSurveyed,
                    'voter_ratio_pct' => $ratio,
                    'survey_coverage_pct' => $coverage,
                    'booth_count' => $villageBooths->count(),
                    'booth_numbers' => $boothNos,
                    'incharge_name' => $villageBooths[0]->incharge_name,
                    'incharge_phone' => $villageBooths[0]->incharge_phone,
                    'key_issue' => $villageBooths[0]->key_issue_mr,
                ];

                foreach ($villageBooths as $vb) {
                    $context['structured_items'][] = [
                        'type' => 'booth',
                        'id' => $vb->id,
                        'title' => "{$vb->village_mr} ({$vb->booth_no})",
                        'subtitle' => "लोकसंख्या: " . number_format($vb->total_population) . " • मतदार: " . number_format($vb->total_voters) . " ({$vb->coverage_pct}% पाहणी)",
                        'incharge' => "प्रभारी: {$vb->incharge_name} ({$vb->incharge_phone})",
                    ];
                }

                $context['suggested_actions'] = [
                    ['label' => $isMr ? "📊 सर्व गावांची लोकसंख्या यादी" : "All Villages Demographics", 'query' => $isMr ? "गावानुसार एकूण लोकसंख्या आणि मतदार संख्या दाखवा" : "Show village population and total voters summary"],
                    ['label' => $isMr ? "⚠️ {$matchedVillageKeyword} मधील समस्या" : "Issues in {$matchedVillageKeyword}", 'query' => "{$matchedVillageKeyword} मधील नागरी समस्या कोणत्या आहेत?"],
                    ['label' => $isMr ? "🗳️ या गावातील मतदार यादी" : "Voter List in this Village", 'query' => "{$matchedVillageKeyword} मधील मतदार दाखवा"],
                ];

                return $context;
            }
        }

        // General village demographics query (all villages / village list)
        if ($isVillageQuery && (
            str_contains($lower, 'लोकसंख्या') || str_contains($lower, 'population') ||
            str_contains($lower, 'गावानुसार') || str_contains($lower, 'गावांची') ||
            str_contains($lower, 'गावातील') || str_contains($lower, 'गाव') ||
            str_contains($lower, 'यादी') || str_contains($lower, 'आकडेवारी') ||
            str_contains($lower, 'सर्व') || str_contains($lower, 'village')
        )) {
            $context['intent'] = 'ALL_VILLAGES_DEMOGRAPHICS';

            $villages = DB::table('neta_booth_progress')
                ->selectRaw('village_mr, village_en, taluka_mr, SUM(total_population) as total_pop, SUM(total_voters) as total_voters, SUM(surveyed_voters) as total_surveyed, COUNT(id) as booth_count')
                ->groupBy('village_mr', 'village_en', 'taluka_mr')
                ->orderByDesc('total_voters')
                ->get();

            $grandPop = $villages->sum('total_pop');
            $grandVoters = $villages->sum('total_voters');
            $grandSurveyed = $villages->sum('total_surveyed');
            $grandRatio = $grandPop > 0 ? round(($grandVoters / $grandPop) * 100, 1) : 0;

            $context['data'] = [
                'total_villages_count' => $villages->count(),
                'grand_total_population' => $grandPop,
                'grand_total_voters' => $grandVoters,
                'grand_total_surveyed' => $grandSurveyed,
                'grand_voter_ratio_pct' => $grandRatio,
                'villages_list' => $villages->toArray(),
            ];

            foreach ($villages->take(6) as $v) {
                $vRatio = $v->total_pop > 0 ? round(($v->total_voters / $v->total_pop) * 100, 1) : 0;
                $context['structured_items'][] = [
                    'type' => 'booth',
                    'title' => "🏘️ {$v->village_mr}",
                    'subtitle' => "लोकसंख्या: " . number_format($v->total_pop) . " | मतदार: " . number_format($v->total_voters) . " ({$vRatio}%)",
                ];
            }

            $context['suggested_actions'] = [
                ['label' => $isMr ? "🏘️ माधवनगर लोकसंख्या व मतदार" : "Madhavnagar Population", 'query' => "माधवनगर ची लोकसंख्या आणि मतदार किती आहेत?"],
                ['label' => $isMr ? "🏘️ बुधगाव लोकसंख्या व मतदार" : "Budhgaon Population", 'query' => "बुधगाव मधील एकूण मतदार आणि लोकसंख्या किती आहे?"],
                ['label' => $isMr ? "🏘️ बामणी लोकसंख्या व मतदार" : "Bamani Population", 'query' => "बामणी गावची लोकसंख्या आणि मतदार किती आहेत?"],
                ['label' => $isMr ? "📊 एकूण मतदार सारांश" : "Total Voters Summary", 'query' => "एकूण मतदार संख्या आणि राजकीय कल दाखवा"],
            ];

            return $context;
        }

        // 2. Specific Citizen Lookup (Search by name or voter ID or phone)
        $cleanPhone = preg_replace('/[^0-9]/', '', $message);
        $matchedCitizens = [];

        if (strlen($cleanPhone) >= 7) {
            $matchedCitizens = DB::table('neta_citizens')
                ->where('phone', 'like', "%{$cleanPhone}%")
                ->orWhere('alternate_phone', 'like', "%{$cleanPhone}%")
                ->get();

            if ($matchedCitizens->isEmpty() && strlen($cleanPhone) >= 10) {
                $context['intent'] = 'NOT_FOUND_CITIZEN';
                $context['data']['query'] = $cleanPhone;
                return $context;
            }
        }

        if (empty($matchedCitizens) && preg_match('/(?:vtr|mh|epic)[a-zA-Z0-9\/_-]{3,}/i', $message, $epicMatch)) {
            $matchedCitizens = DB::table('neta_citizens')
                ->where('voter_id_number', 'like', "%{$epicMatch[0]}%")
                ->get();

            if ($matchedCitizens->isEmpty()) {
                $context['intent'] = 'NOT_FOUND_CITIZEN';
                $context['data']['query'] = $epicMatch[0];
                return $context;
            }
        }

        // Name search (extract keywords)
        $isVoterExplicitWord = str_contains($lower, 'मतदार') || str_contains($lower, 'voter') ||
                               str_contains($lower, 'नागरिक') || str_contains($lower, 'citizen') ||
                               str_contains($lower, 'शोधा') || str_contains($lower, 'search');

        $words = preg_split('/\s+/', trim($message));
        $isShortNameQuery = count($words) <= 3 && !preg_match('/[?!=]/', $message);

        if (empty($matchedCitizens) && ($isVoterExplicitWord || $isShortNameQuery)) {
            foreach ($words as $w) {
                $w = trim($w, ",.?! ");
                if (mb_strlen($w) >= 3 && !in_array(mb_strtolower($w), [
                    'माहिती', 'दाखवा', 'द्या', 'आहे', 'काय', 'शोधा', 'show', 'voter', 'details', 'info',
                    'मतदार', 'नागरिक', 'citizen', 'please', 'give', 'list', 'status', 'summary', 'about',
                    'सांगा', 'तपशील', 'कोण', 'कशी', 'कोणते', 'पाहिजे', 'पाहिजेत', 'एकूण', 'नंबर'
                ])) {
                    $citizens = DB::table('neta_citizens')
                        ->where('full_name_mr', 'like', "%{$w}%")
                        ->orWhere('full_name_en', 'like', "%{$w}%")
                        ->get();
                    if ($citizens->isNotEmpty()) {
                        $matchedCitizens = $citizens;
                        break;
                    }
                }
            }
        }

        if (!empty($matchedCitizens) && count($matchedCitizens) > 0) {
            $context['intent'] = 'CITIZEN_LOOKUP';
            $context['data']['citizens'] = $matchedCitizens;
            foreach ($matchedCitizens as $c) {
                $context['structured_items'][] = [
                    'type' => 'citizen',
                    'id' => $c->id,
                    'title' => $c->full_name_mr,
                    'subtitle' => "EPIC: {$c->voter_id_number} • {$c->booth_no}",
                    'sentiment' => $c->sentiment_label_mr ?? $c->sentiment,
                    'phone' => $c->phone,
                    'address' => $c->address_mr,
                ];
            }
            $context['suggested_actions'] = [
                ['label' => $isMr ? 'या मतदाराच्या कुटुंबातील सदस्य' : 'Family Members', 'query' => "{$matchedCitizens[0]->full_name_mr} यांच्या कुटुंबातील मतदार"],
                ['label' => $isMr ? 'यांचे बूथ तपशील' : 'Booth Details', 'query' => "{$matchedCitizens[0]->booth_no} चे तपशील दाखवा"],
            ];
            return $context;
        }

        // 3. Booth Lookup / Status
        if (str_contains($lower, 'booth') || str_contains($lower, 'बूथ') || str_contains($lower, 'प्रभाग') || str_contains($lower, 'ward')) {
            $context['intent'] = 'BOOTH_INFO';
            // Check if specific booth number is mentioned
            if (preg_match('/(?:बूथ|booth|ward|प्रभाग)\s*(?:क्र\.|no\.?|क्रमांक)?\s*([०-९0-9]+)/iu', $message, $bMatch)) {
                $bNum = $bMatch[1];
                $marathiDigits = ['०'=>'0','१'=>'1','२'=>'2','३'=>'3','४'=>'4','५'=>'5','६'=>'6','७'=>'7','८'=>'8','९'=>'9'];
                $cleanBNum = strtr($bNum, $marathiDigits);

                $specificBooth = DB::table('neta_booth_progress')
                    ->where('booth_no', 'like', "%{$cleanBNum}%")
                    ->orWhere('booth_no', 'like', "%{$bNum}%")
                    ->first();

                if ($specificBooth) {
                    $context['data']['specific_booth'] = $specificBooth;
                    $context['data']['booth_citizens'] = DB::table('neta_citizens')
                        ->where('booth_no', 'like', "%{$cleanBNum}%")
                        ->orWhere('booth_no', 'like', "%{$bNum}%")
                        ->limit(5)->get();
                    $context['structured_items'][] = [
                        'type' => 'booth',
                        'title' => $specificBooth->booth_no . ' - ' . $specificBooth->booth_name_mr,
                        'subtitle' => "मतदार: {$specificBooth->total_voters} | पाहणी: {$specificBooth->surveyed_voters} ({$specificBooth->coverage_pct}%)",
                        'incharge' => "प्रभारी: {$specificBooth->incharge_name} ({$specificBooth->incharge_phone})",
                    ];
                    $context['suggested_actions'] = [
                        ['label' => $isMr ? 'या बूथवरील मतदार यादी' : 'Voters in this Booth', 'query' => "{$specificBooth->booth_no} मधील मतदार दाखवा"],
                        ['label' => $isMr ? 'या बूथवरील नागरी समस्या' : 'Issues in this Booth', 'query' => "{$specificBooth->booth_no} वरील समस्या"],
                    ];
                    return $context;
                } else {
                    $context['intent'] = 'NOT_FOUND_BOOTH';
                    $context['data']['booth_no'] = $cleanBNum;
                    return $context;
                }
            }

            // General booth summary
            $context['data']['total_booths'] = DB::table('neta_booth_progress')->count();
            $context['data']['top_booths'] = DB::table('neta_booth_progress')->orderBy('coverage_pct', 'desc')->limit(5)->get();
            $context['data']['all_booths_summary'] = DB::table('neta_booth_progress')
                ->selectRaw('SUM(total_voters) as total_voters, SUM(surveyed_voters) as total_surveyed, AVG(coverage_pct) as avg_coverage')
                ->first();

            foreach ($context['data']['top_booths'] as $tb) {
                $context['structured_items'][] = [
                    'type' => 'booth',
                    'title' => $tb->booth_no . ' (' . $tb->village_mr . ')',
                    'subtitle' => "पाहणी कव्हरेज: {$tb->coverage_pct}% • मतदार: {$tb->surveyed_voters}/{$tb->total_voters}",
                ];
            }
            return $context;
        }

        // 4. Civic Issues / Grievances
        if (str_contains($lower, 'issue') || str_contains($lower, 'problem') || str_contains($lower, 'complaint') ||
            str_contains($lower, 'grievance') || str_contains($lower, 'समस्या') || str_contains($lower, 'तक्रार') ||
            str_contains($lower, 'पाणी') || str_contains($lower, 'रस्ता') || str_contains($lower, 'ड्रेनेज') ||
            str_contains($lower, 'खड्डे') || str_contains($lower, 'लाईट') || str_contains($lower, 'विद्युत') ||
            str_contains($lower, 'कचरा')) {
            $context['intent'] = 'CIVIC_ISSUES';
            $context['data']['total_issues'] = DB::table('neta_crm_issues')->count();
            $context['data']['pending_issues'] = DB::table('neta_crm_issues')->where('status', 'PENDING')->count();
            $context['data']['resolved_issues'] = DB::table('neta_crm_issues')->where('status', 'RESOLVED')->count();
            $context['data']['issues_list'] = DB::table('neta_crm_issues')->orderBy('id', 'desc')->limit(6)->get();

            foreach ($context['data']['issues_list'] as $iss) {
                $context['structured_items'][] = [
                    'type' => 'issue',
                    'title' => $iss->title_mr,
                    'subtitle' => "ट्रॅकिंग क्र: {$iss->tracking_no} • प्राधान्य: {$iss->priority}",
                    'status' => $iss->status,
                    'assigned_worker' => $iss->assigned_worker,
                ];
            }
            $context['suggested_actions'] = [
                ['label' => $isMr ? 'रस्त्यांच्या समस्या' : 'Road Issues', 'query' => 'रस्ते व डांबरीकरण संबंधित तक्रारी'],
                ['label' => $isMr ? 'ड्रेनेज व पाणीपुरवठा' : 'Water & Drainage', 'query' => 'ड्रेनेज व पाणी समस्या'],
            ];
            return $context;
        }

        // 5. Karyakartas / Field Workers (including direct worker name matching)
        $matchedWorker = null;
        if (mb_strlen($message) >= 3) {
            $matchedWorker = DB::table('neta_karyakartas')
                ->where('name', 'like', "%{$message}%")
                ->orWhere('assigned_area_mr', 'like', "%{$message}%")
                ->orWhere('assigned_area_en', 'like', "%{$message}%")
                ->first();
        }

        if ($matchedWorker || str_contains($lower, 'karyakarta') || str_contains($lower, 'worker') || str_contains($lower, 'कार्यकर्त') ||
            str_contains($lower, 'सहकारी') || str_contains($lower, 'टीम') || str_contains($lower, 'स्वयंसेवक')) {
            $context['intent'] = 'KARYAKARTAS';
            $context['data']['total_karyakartas'] = DB::table('neta_karyakartas')->count();
            $context['data']['active_karyakartas'] = DB::table('neta_karyakartas')->where('status', 'active')->count();
            $context['data']['top_karyakartas'] = DB::table('neta_karyakartas')->orderBy('total_surveys', 'desc')->limit(6)->get();

            if ($matchedWorker) {
                $context['data']['specific_worker'] = $matchedWorker;
                $context['structured_items'][] = [
                    'type' => 'worker',
                    'title' => $matchedWorker->name,
                    'subtitle' => "पद: {$matchedWorker->role_mr} • क्षेत्र: {$matchedWorker->assigned_area_mr} • सर्व्हे: {$matchedWorker->total_surveys}",
                    'phone' => $matchedWorker->phone,
                    'rating' => $matchedWorker->rating,
                ];
            } else {
                foreach ($context['data']['top_karyakartas'] as $k) {
                    $context['structured_items'][] = [
                        'type' => 'worker',
                        'title' => $k->name,
                        'subtitle' => "क्षेत्र: {$k->assigned_area_mr} • सर्व्हे: {$k->total_surveys}",
                        'phone' => $k->phone,
                        'rating' => $k->rating,
                    ];
                }
            }
            return $context;
        }

        // 6. Submissions & Data Approvals
        if (str_contains($lower, 'submission') || str_contains($lower, 'approval') || str_contains($lower, 'मंजूर') ||
            str_contains($lower, 'मंजुरी') || str_contains($lower, 'अर्ज') || str_contains($lower, 'पाहणी नोंद') ||
            str_contains($lower, 'सर्व्हे नोंद') || str_contains($lower, 'नोंदी') || str_contains($lower, 'तपासणी') ||
            str_contains($lower, 'पडताळणी') || str_contains($lower, 'नोंदणी')) {
            $context['intent'] = 'SUBMISSIONS_APPROVAL';
            $context['data']['total_submissions'] = DB::table('admin_field_submissions')->count();
            $context['data']['pending_approvals'] = DB::table('admin_field_submissions')->where('status', 'PENDING_APPROVAL')->count();
            $context['data']['approved_count'] = DB::table('admin_field_submissions')->where('status', 'APPROVED')->count();
            $context['data']['rejected_count'] = DB::table('admin_field_submissions')->where('status', 'REJECTED')->count();
            $context['data']['recent_submissions'] = DB::table('admin_field_submissions')->orderBy('id', 'desc')->limit(4)->get();

            foreach ($context['data']['recent_submissions'] as $sub) {
                $context['structured_items'][] = [
                    'type' => 'submission',
                    'title' => $sub->submission_code . ' - ' . $sub->citizen_name_mr,
                    'subtitle' => "बूथ: {$sub->booth_no} • सादरकर्ता: {$sub->submitted_by_name}",
                    'status' => $sub->status,
                ];
            }
            return $context;
        }

        // 7. Official Documents
        if (str_contains($lower, 'doc') || str_contains($lower, 'दस्तऐवज') || str_contains($lower, 'कागदपत्र') ||
            str_contains($lower, 'pdf') || str_contains($lower, 'परिपत्रक') || str_contains($lower, 'अहवाल')) {
            $context['intent'] = 'OFFICIAL_DOCUMENTS';
            $context['data']['total_documents'] = DB::table('admin_media_documents')->count();
            $context['data']['docs'] = DB::table('admin_media_documents')->orderBy('id', 'desc')->limit(6)->get();

            foreach ($context['data']['docs'] as $d) {
                $context['structured_items'][] = [
                    'type' => 'document',
                    'title' => $d->title_mr,
                    'subtitle' => "फाइल: {$d->file_name} ({$d->file_size_formatted}) • प्रवर्ग: {$d->category}",
                    'booth' => $d->booth_ref,
                ];
            }
            return $context;
        }

        // 8. Government Schemes
        if (str_contains($lower, 'scheme') || str_contains($lower, 'योजना') || str_contains($lower, 'लाडकी बहीण') ||
            str_contains($lower, 'लाभार्थी') || str_contains($lower, 'beneficiar')) {
            $context['intent'] = 'SCHEMES_INFO';
            $context['data']['citizens_with_schemes'] = DB::table('neta_citizens')
                ->whereNotNull('schemes_benefitted')
                ->where('schemes_benefitted', '!=', '[]')
                ->where('schemes_benefitted', '!=', '')
                ->count();
            return $context;
        }

        // 9. General Overview / Campaign Summary (Explicit statistics, totals, or dashboard summaries)
        $overviewKeywords = [
            'overview', 'summary', 'dashboard', 'report', 'stats', 'statistics', 'briefing',
            'एकूण सारांश', 'सद्यस्थिती', 'डॅशबोर्ड', 'अहवाल', 'आकडेवारी', 'एकूण मतदार संख्या', 'एकूण आकडेवारी',
            'मोहीम सारांश', 'प्रगती अहवाल', 'constituency report', 'campaign stats'
        ];
        $isOverview = false;
        foreach ($overviewKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $isOverview = true;
                break;
            }
        }

        if (str_contains($lower, 'एकूण मतदार') || str_contains($lower, 'total voters') || str_contains($lower, 'मतदार संख्या')) {
            $isOverview = true;
        }

        if ($isOverview) {
            $context['intent'] = 'GENERAL_OVERVIEW';
            $context['data']['citizens_total'] = DB::table('neta_citizens')->count();
            $context['data']['sentiment_anukul'] = DB::table('neta_citizens')->where('sentiment', 'anukul')->count();
            $context['data']['sentiment_neutral'] = DB::table('neta_citizens')->where('sentiment', 'neutral')->count();
            $context['data']['sentiment_pratikul'] = DB::table('neta_citizens')->where('sentiment', 'pratikul')->count();
            $context['data']['total_booths'] = DB::table('neta_booth_progress')->count();
            $context['data']['total_karyakartas'] = DB::table('neta_karyakartas')->count();
            $context['data']['pending_issues'] = DB::table('neta_crm_issues')->where('status', 'PENDING')->count();
            $context['data']['pending_submissions'] = DB::table('admin_field_submissions')->where('status', 'PENDING_APPROVAL')->count();
            $context['data']['total_documents'] = DB::table('admin_media_documents')->count();
            $context['data']['total_projects'] = DB::table('projects')->count();
            return $context;
        }

        // 10. Fallback: UNKNOWN_QUERY (The query was not understood)
        $context['intent'] = 'UNKNOWN_QUERY';
        $context['data']['original_query'] = $message;
        return $context;
    }

    /**
     * Formats an executive response based on verified real-world database metrics.
     */
    private function formatSmartResponse(string $message, array $context, bool $isMr): string
    {
        $intent = $context['intent'];
        $d = $context['data'];

        // 1. Unrecognized / Misunderstood Query
        if ($intent === 'UNKNOWN_QUERY') {
            if ($isMr) {
                return "⚠️ क्षमस्व, मला आपला प्रश्न समजला नाही. कृपया पुन्हा विचारा किंवा खालील बटणावर क्लिक करा.";
            } else {
                return "⚠️ Sorry, I didn't understand that. Please ask again or tap a suggestion below.";
            }
        }

        // 2. Greetings
        if ($intent === 'GREETING') {
            if ($isMr) {
                return "👋 नमस्कार! मी आपला निवडणूक व प्रचार सहाय्यक आहे. मी आपल्याला कशी मदत करू?";
            } else {
                return "👋 Hello! I am your Campaign & Field Operations Assistant. How can I help you today?";
            }
        }

        // 3. Citizen Not Found
        if ($intent === 'NOT_FOUND_CITIZEN') {
            $q = htmlspecialchars($d['query'] ?? '');
            if ($isMr) {
                return "⚠️ '{$q}' या नावाची किंवा क्रमांकाची नोंद सापडली नाही. कृपया तपासून पुन्हा मेसेज पाठवा.";
            } else {
                return "⚠️ No voter found for '{$q}'. Please check and send again.";
            }
        }

        // 4. Booth Not Found
        if ($intent === 'NOT_FOUND_BOOTH') {
            $b = htmlspecialchars($d['booth_no'] ?? '');
            if ($isMr) {
                return "⚠️ बूथ क्र. {$b} ची माहिती आढळली नाही. कृपया योग्य बूथ क्रमांक पाठवा.";
            } else {
                return "⚠️ Booth {$b} not found. Please check and send again.";
            }
        }

        // 5. Schemes Information
        if ($intent === 'SCHEMES_INFO') {
            $count = $d['citizens_with_schemes'] ?? 0;
            if ($isMr) {
                return "🏛️ **शासकीय योजना:** एकूण **{$count} लाभार्थी मतदार** नोंदणीकृत आहेत.";
            } else {
                return "🏛️ **Schemes:** Total **{$count} voter beneficiaries** registered.";
            }
        }

        // 5.5 Specific Village Demographics (Population & Voters)
        if ($intent === 'SPECIFIC_VILLAGE_DEMOGRAPHICS') {
            $fullVName = $d['full_village_name'] ?? $d['village_name'];
            $pop = number_format($d['total_population'] ?? 0);
            $voters = number_format($d['total_voters'] ?? 0);
            $surveyed = number_format($d['surveyed_voters'] ?? 0);
            $ratio = $d['voter_ratio_pct'] ?? 0;
            $coverage = $d['survey_coverage_pct'] ?? 0;
            $booths = $d['booth_numbers'] ?? '-';
            $bCount = $d['booth_count'] ?? 1;
            $incharge = !empty($d['incharge_name']) ? "{$d['incharge_name']} ({$d['incharge_phone']})" : '-';
            $issue = !empty($d['key_issue']) ? $d['key_issue'] : ($isMr ? 'काही नाही' : 'None');

            if ($isMr) {
                return "🏘️ **{$fullVName} — लोकसंख्या व मतदार अहवाल:**\n" .
                       "• 👥 **एकूण लोकसंख्या (Total Population):** **{$pop}** नागरिक\n" .
                       "• 🗳️ **एकूण मतदार संख्या (Total Voters):** **{$voters}** मतदार\n" .
                       "• 📊 **मतदार ते लोकसंख्या प्रमाण:** **{$ratio}%**\n" .
                       "• 📋 **प्रत्यक्ष पाहणी झालेले मतदार:** **{$surveyed}** ({$coverage}% कव्हरेज)\n" .
                       "• 🗳️ **समाविष्ट मतदान केंद्र (Booths):** {$bCount} बूथ ({$booths})\n" .
                       "• 👤 **बूथ/गाव प्रमुख:** {$incharge}\n" .
                       "• ⚠️ **स्थानिक प्रमुख समस्या:** {$issue}";
            } else {
                return "🏘️ **{$fullVName} — Population & Voter Demographics:**\n" .
                       "• 👥 **Total Village Population:** **{$pop}** residents\n" .
                       "• 🗳️ **Total Registered Voters:** **{$voters}** voters\n" .
                       "• 📊 **Voter-to-Population Ratio:** **{$ratio}%**\n" .
                       "• 📋 **Surveyed Voters:** **{$surveyed}** ({$coverage}% coverage)\n" .
                       "• 🗳️ **Polling Booths:** {$bCount} booths ({$booths})\n" .
                       "• 👤 **In-charge:** {$incharge}\n" .
                       "• ⚠️ **Key Local Issue:** {$issue}";
            }
        }

        // 5.6 All Villages Demographics (Summary Table)
        if ($intent === 'ALL_VILLAGES_DEMOGRAPHICS') {
            $totVillages = $d['total_villages_count'] ?? 0;
            $grandPop = number_format($d['grand_total_population'] ?? 0);
            $grandVoters = number_format($d['grand_total_voters'] ?? 0);
            $grandRatio = $d['grand_voter_ratio_pct'] ?? 0;

            if ($isMr) {
                $table = "| गाव / प्रभाग | एकूण लोकसंख्या | एकूण मतदार | मतदार प्रमाण | कव्हरेज % |\n";
                $table .= "| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($d['villages_list'] ?? [] as $v) {
                    $vObj = (object)$v;
                    $vPop = number_format($vObj->total_pop);
                    $vVoters = number_format($vObj->total_voters);
                    $vRatio = $vObj->total_pop > 0 ? round(($vObj->total_voters / $vObj->total_pop) * 100, 1) : 0;
                    $vCov = $vObj->total_voters > 0 ? round(($vObj->total_surveyed / $vObj->total_voters) * 100, 1) : 0;
                    $table .= "| **{$vObj->village_mr}** | {$vPop} | {$vVoters} | {$vRatio}% | {$vCov}% |\n";
                }

                return "🏘️ **गावनिहाय एकूण लोकसंख्या व मतदार संख्या सारांश:**\n" .
                       "• एकूण गावे व प्रभाग: **{$totVillages}**\n" .
                       "• एकूण अंदाजित लोकसंख्या: **{$grandPop}** नागरिक\n" .
                       "• एकूण नोंदणीकृत मतदार: **{$grandVoters}** मतदार (प्रमाण: **{$grandRatio}%**)\n\n" .
                       $table;
            } else {
                $table = "| Village / Ward | Total Population | Registered Voters | Ratio % | Coverage % |\n";
                $table .= "| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($d['villages_list'] ?? [] as $v) {
                    $vObj = (object)$v;
                    $vPop = number_format($vObj->total_pop);
                    $vVoters = number_format($vObj->total_voters);
                    $vRatio = $vObj->total_pop > 0 ? round(($vObj->total_voters / $vObj->total_pop) * 100, 1) : 0;
                    $vCov = $vObj->total_voters > 0 ? round(($vObj->total_surveyed / $vObj->total_voters) * 100, 1) : 0;
                    $table .= "| **{$vObj->village_mr}** | {$vPop} | {$vVoters} | {$vRatio}% | {$vCov}% |\n";
                }

                return "🏘️ **Village-wise Population & Voter Demographics:**\n" .
                       "• Total Villages/Wards: **{$totVillages}**\n" .
                       "• Total Population: **{$grandPop}** residents\n" .
                       "• Total Voters: **{$grandVoters}** voters (Ratio: **{$grandRatio}%**)\n\n" .
                       $table;
            }
        }

        // 6. Citizen Details
        if ($intent === 'CITIZEN_LOOKUP') {
            $c = $d['citizens'][0];
            $phone = $c->phone ?: ($isMr ? 'नाही' : 'N/A');
            $epic = $c->voter_id_number ?: '-';
            $sentiment = $c->sentiment_label_mr ?? $c->sentiment ?? '-';

            if ($isMr) {
                return "👤 **{$c->full_name_mr}**\n• मतदार क्र: `{$epic}`\n• मोबाईल: {$phone}\n• बूथ: {$c->booth_no}\n• राजकीय कल: 🟢 {$sentiment}";
            } else {
                return "👤 **{$c->full_name_mr}**\n• Voter ID: `{$epic}`\n• Phone: {$phone}\n• Booth: {$c->booth_no}\n• Sentiment: {$sentiment}";
            }
        }

        // 7. Booth Info
        if ($intent === 'BOOTH_INFO') {
            if (!empty($d['specific_booth'])) {
                $sb = $d['specific_booth'];
                if ($isMr) {
                    return "🗳️ **बूथ क्र. {$sb->booth_no} ({$sb->booth_name_mr})**\n• एकूण मतदार: **{$sb->total_voters}**\n• पाहणी: **{$sb->surveyed_voters}** ({$sb->coverage_pct}%)\n• प्रभारी: {$sb->incharge_name} ({$sb->incharge_phone})";
                } else {
                    return "🗳️ **Booth {$sb->booth_no} ({$sb->booth_name_en})**\n• Total Voters: **{$sb->total_voters}**\n• Surveyed: **{$sb->surveyed_voters}** ({$sb->coverage_pct}%)\n• Incharge: {$sb->incharge_name} ({$sb->incharge_phone})";
                }
            }

            $totalBooths = $d['total_booths'];
            $topList = "";
            $count = 0;
            foreach ($d['top_booths'] as $tb) {
                if (++$count > 3) break;
                $topList .= "• {$tb->booth_no} ({$tb->village_mr}): {$tb->coverage_pct}% पाहणी\n";
            }

            if ($isMr) {
                return "🗳️ एकूण **{$totalBooths} बूथ** सक्रिय आहेत.\nआघाडीवरील बूथ:\n" . rtrim($topList);
            } else {
                $enTopList = "";
                $count = 0;
                foreach ($d['top_booths'] as $tb) {
                    if (++$count > 3) break;
                    $enTopList .= "• Booth {$tb->booth_no}: {$tb->coverage_pct}% coverage\n";
                }
                return "🗳️ Total **{$totalBooths} booths** active.\nTop performing booths:\n" . rtrim($enTopList);
            }
        }

        // 8. Civic Issues
        if ($intent === 'CIVIC_ISSUES') {
            $tot = $d['total_issues'];
            $pend = $d['pending_issues'];
            $res = $d['resolved_issues'];

            if ($isMr) {
                return "⚠️ **नागरी समस्या:**\n• एकूण: **{$tot}** (प्रलंबित: 🔴 **{$pend}**, निवारण: 🟢 **{$res}**)";
            } else {
                return "⚠️ **Civic Issues:**\n• Total: **{$tot}** (Pending: 🔴 **{$pend}**, Resolved: 🟢 **{$res}**)";
            }
        }

        // 9. Karyakartas / Field Workers
        if ($intent === 'KARYAKARTAS') {
            if (!empty($d['specific_worker'])) {
                $w = $d['specific_worker'];
                if ($isMr) {
                    return "👤 **कार्यकर्ता माहिती: {$w->name}**\n" .
                           "• पद/भूमिका: **{$w->role_mr}**\n" .
                           "• नेमून दिलेले क्षेत्र: **{$w->assigned_area_mr}**\n" .
                           "• मोबाईल क्रमांक: `{$w->phone}`\n" .
                           "• एकूण पूर्ण सर्व्हे: **{$w->total_surveys}** (आजचे: **{$w->today_surveys}**)\n" .
                           "• कव्हरेज प्रमाण: **{$w->coverage_pct}%** (कामगिरी रेटिंग: ⭐ **{$w->rating}/5**)";
                } else {
                    return "👤 **Worker Details: {$w->name}**\n" .
                           "• Role: **{$w->role_en}**\n" .
                           "• Assigned Area: **{$w->assigned_area_en}**\n" .
                           "• Phone: `{$w->phone}`\n" .
                           "• Total Surveys: **{$w->total_surveys}** (Today: **{$w->today_surveys}**)\n" .
                           "• Coverage: **{$w->coverage_pct}%** (Rating: ⭐ **{$w->rating}/5**)";
                }
            }

            $tot = $d['total_karyakartas'] ?? DB::table('neta_karyakartas')->count();
            $act = $d['active_karyakartas'] ?? DB::table('neta_karyakartas')->where('status', 'active')->count();
            $topK = $d['top_karyakartas'] ?? DB::table('neta_karyakartas')->orderBy('total_surveys', 'desc')->limit(6)->get();

            $kList = "";
            $count = 0;
            foreach ($topK as $k) {
                if (++$count > 4) break;
                $kList .= "• **{$k->name}** — {$k->assigned_area_mr} (**{$k->total_surveys}** सर्व्हे, ⭐ {$k->rating})\n";
            }

            if ($isMr) {
                return "👥 **कार्यकर्ते कामगिरी अहवाल:**\n" .
                       "• एकूण कार्यकर्ते: **{$tot}** (सक्रिय: **{$act}**)\n\n" .
                       "🏆 **आघाडीचे सक्रिय कार्यकर्ते:**\n" . rtrim($kList);
            } else {
                $enKList = "";
                $count = 0;
                foreach ($topK as $k) {
                    if (++$count > 4) break;
                    $enKList .= "• **{$k->name}** — {$k->assigned_area_en} (**{$k->total_surveys}** surveys, ⭐ {$k->rating})\n";
                }
                return "👥 **Field Workers Performance:**\n" .
                       "• Total Workers: **{$tot}** (Active: **{$act}**)\n\n" .
                       "🏆 **Top Active Workers:**\n" . rtrim($enKList);
            }
        }

        // 10. Submissions & Approvals
        if ($intent === 'SUBMISSIONS_APPROVAL') {
            $tot = $d['total_submissions'] ?? DB::table('admin_field_submissions')->count();
            $pend = $d['pending_approvals'] ?? DB::table('admin_field_submissions')->where('status', 'PENDING_APPROVAL')->count();
            $app = $d['approved_count'] ?? DB::table('admin_field_submissions')->where('status', 'APPROVED')->count();
            $rej = $d['rejected_count'] ?? DB::table('admin_field_submissions')->where('status', 'REJECTED')->count();

            if ($isMr) {
                return "📋 **फील्ड सर्व्हे नोंदणी व मंजुरी अहवाल:**\n" .
                       "• एकूण सादर नोंदी: **{$tot}**\n" .
                       "• तपासणीसाठी प्रलंबित: 🟠 **{$pend} नोंदी**\n" .
                       "• मंजूर (Approved): 🟢 **{$app} नोंदी**\n" .
                       "• नाकारलेले (Rejected): 🔴 **{$rej} नोंदी**\n\n" .
                       "💡 प्रलंबित नोंदी तपासण्यासाठी 'फील्ड डेटा मंजुरी' कक्षात जाऊन मंजुरी किंवा दुरुस्ती करू शकता.";
            } else {
                return "📋 **Field Survey Submissions & Approvals:**\n" .
                       "• Total Submissions: **{$tot}**\n" .
                       "• Pending Verification: 🟠 **{$pend}**\n" .
                       "• Approved: 🟢 **{$app}**\n" .
                       "• Rejected: 🔴 **{$rej}**\n\n" .
                       "💡 You can review and approve submissions in the Data Approval Desk.";
            }
        }

        // 11. Official Documents
        if ($intent === 'OFFICIAL_DOCUMENTS') {
            $tot = $d['total_documents'] ?? DB::table('admin_media_documents')->count();
            if ($isMr) {
                return "📁 **दस्तऐवज भांडार:** केंद्रीय भांडारात एकूण **{$tot} अधिकृत शासकीय व मोहीम फाइल्स** उपलब्ध आहेत.";
            } else {
                return "📁 **Official Documents:** Total **{$tot} files** available in central repository.";
            }
        }

        // 12. Campaign Strategy / Slogans / Speeches / Advice (Offline & Direct)
        if ($intent === 'CAMPAIGN_STRATEGY') {
            // Check specific sub-intent: slogans, speech, social media, or door-to-door
            if (preg_match('/(?:घोषणा|slogan)/iu', $message)) {
                if ($isMr) {
                    return "📢 **निवडणूक प्रचार घोषणा (Campaign Slogans):**\n" .
                           "• *\"कामाचा ध्यास, विकासाचा विश्वास! आपलं एक मत, प्रगतीचा संकल्प!\"*\n" .
                           "• *\"प्रत्येक घरात समृद्धी, प्रत्येक गावात विकास! जनसेवेचा एकच ध्यास!\"*\n" .
                           "• *\"युवकांना रोजगार, बळीराजाला सन्मान! चला घडवूया नवा महाराष्ट्र!\"*\n" .
                           "• *\"आपला हक्काचा माणूस, आपल्या हक्काचा विकास!\"*";
                } else {
                    return "📢 **Campaign Slogans:**\n" .
                           "• *\"Commitment to Work, Trust in Progress! Your vote, our pledge!\"*\n" .
                           "• *\"Prosperity in every home, development in every village!\"*\n" .
                           "• *\"Empowerment for youth, dignity for farmers!\"*";
                }
            }

            if (preg_match('/(?:भाषण|speech|मुद्दे)/iu', $message)) {
                if ($isMr) {
                    return "🎤 **सभेसाठी भाषणाचे प्रमुख मुद्दे (Speech Points):**\n" .
                           "1. **प्रस्तावना:** उपस्थितांचे आभार व मतदारसंघाशी असलेली आपुलकी.\n" .
                           "2. **स्थानिक विकास:** रस्ते, पाणी, वीज व आरोग्यासाठी केलेली व प्रस्तावित कामे.\n" .
                           "3. **शासकीय योजना:** लाडकी बहीण, शेतकरी सन्मान निधी योजनांचा लाभ प्रत्येक घरापर्यंत.\n" .
                           "4. **भविष्यकालीन संकल्प:** तरुणांसाठी स्थानिक रोजगार व पायाभूत सुविधा.\n" .
                           "5. **आवाहन:** विकासाच्या पाठीशी ठाम उभे राहून भरघोस मतांनी विजयी करण्याचे आवाहन.";
                } else {
                    return "🎤 **Key Rally Speech Outline:**\n" .
                           "1. **Greeting:** Gratitude to citizens and emotional connect to the constituency.\n" .
                           "2. **Local Issues:** Accomplishments and future commitments on roads, water & health.\n" .
                           "3. **Welfare Schemes:** Ensuring 100% reach of farmer & women welfare initiatives.\n" .
                           "4. **Future Vision:** Job creation, educational hubs, and modern infrastructure.\n" .
                           "5. **Call to Action:** Vote for decisive development and reliable leadership.";
                }
            }

            if (preg_match('/(?:सोशल मीडिया|social media|पोस्ट|post)/iu', $message)) {
                if ($isMr) {
                    return "📱 **सोशल मीडिया प्रचार रणनीती (Social Media Tips):**\n" .
                           "• **सकाळची वेळ (8 ते 10 AM):** सकारात्मक विकासकामे व दैनंदिन भेटींचे छोटे 30-सेकंद व्हिडिओ रिल्स.\n" .
                           "• **दुपारची वेळ (1 ते 3 PM):** शासकीय योजना लाभार्थी यशोगाथा (Testimonials) व इन्फोग्राफिक्स.\n" .
                           "• **संध्याकाळची वेळ (7 ते 9 PM):** जाहीर सभा थेट प्रक्षेपण (Live) व प्रमुख आश्वासने.\n" .
                           "• **WhatsApp गट:** प्रत्येक बूथनिहाय स्थानिक प्रश्न व त्वरित निवारणाचे फोटो शेअर करा.";
                } else {
                    return "📱 **Social Media Strategy:**\n" .
                           "• **Morning (8-10 AM):** Positive 30s development reels & morning walk interactions.\n" .
                           "• **Afternoon (1-3 PM):** Beneficiary testimonials and project infographics.\n" .
                           "• **Evening (7-9 PM):** Live rally stream and key speech highlights.\n" .
                           "• **WhatsApp Groups:** Booth-specific updates and quick redressal updates.";
                }
            }

            // General Campaign Advice
            if ($isMr) {
                return "🎯 **प्रचार मोहीम नियोजन व मार्गदर्शन (Campaign Guidance):**\n" .
                       "• **बूथनिहाय संपर्क:** प्रत्येक कार्यकर्त्याला 50 घरांची जबाबदारी देऊन थेट संवाद साधा.\n" .
                       "• **तटस्थ मतदार:** 'तटस्थ (Neutral)' मतदारांच्या घरी जाऊन स्थानिक समस्या जाणून घ्या.\n" .
                       "• **योजना लाभार्थी:** लाडकी बहीण व शेतकरी योजना लाभार्थी कुटुंबांना प्राधान्याने भेटा.\n" .
                       "• **प्रचार फलक:** केलेल्या विकासकामांचे संक्षिप्त फलक प्रमुख चौकात लावा.";
            } else {
                return "🎯 **Field Campaign Guidance:**\n" .
                       "• **Booth-level assignment:** Assign 50 households per karyakarta for personal contact.\n" .
                       "• **Swing voters:** Focus on neutral voters by addressing their micro-local issues.\n" .
                       "• **Beneficiary outreach:** Prioritize visits to welfare scheme beneficiaries.\n" .
                       "• **Visibility:** Position hoardings highlighting verified completed work.";
            }
        }

        // 13. Election Model Code of Conduct
        if ($intent === 'CODE_OF_CONDUCT') {
            if ($isMr) {
                return "📜 **निवडणूक आदर्श आचारसंहिता (Model Code of Conduct):**\n" .
                       "• **ध्वनिक्षेपक (Loudspeaker):** सकाळी ६:०० ते रात्री १०:०० पर्यंतच पूर्वपरवानगीने वापरता येईल.\n" .
                       "• **प्रचार सभा व रॅली:** स्थानिक पोलीस व निवडणूक अधिकाऱ्यांची किमान २४ तास आधी लेखी परवानगी आवश्यक.\n" .
                       "• **मतदान केंद्र (Polling Station):** मतदानाच्या दिवशी केंद्राच्या १०० मीटर परिसरात प्रचार करण्यास सक्त मनाई.\n" .
                       "• **प्रचार समाप्ती:** मतदानाच्या वेळेच्या ४८ तास आधी सर्व जाहीर प्रचार, सभा व भोंगे बंद करणे बंधनकारक.";
            } else {
                return "📜 **Model Code of Conduct Guidelines:**\n" .
                       "• **Loudspeakers:** Permitted only between 6:00 AM and 10:00 PM with official prior approval.\n" .
                       "• **Rallies & Meetings:** Written permission required from Police & RO at least 24 hours in advance.\n" .
                       "• **Polling Stations:** Zero campaigning allowed within 100 meters on voting day.\n" .
                       "• **Campaign Silence:** Public campaigning, rallies, and loudspeakers must stop 48 hours prior to poll close.";
            }
        }

        // 14. Help / Capabilities Guide
        if ($intent === 'HELP_GUIDE') {
            if ($isMr) {
                return "🤖 **मी खालील विषयांवर थेट डेटाबेस माहिती व सल्ला देऊ शकतो:**\n" .
                       "1. 🏘️ **गाव व प्रभाग लोकसंख्या:** (उदा. *'माधवनगर ची लोकसंख्या व मतदार'*, *'गाव लोकसंख्या'*)\n" .
                       "2. 📊 **मतदार व राजकीय कल:** (उदा. *'एकूण मतदार सारांश'*, *'अनुकूल मतदार संख्या'*)\n" .
                       "3. 🗳️ **बूथनिहाय प्रगती:** (उदा. *'बूथ क्र. १ ची माहिती'*, *'आघाडीचे बूथ'*)\n" .
                       "4. ⚠️ **नागरी समस्या:** (उदा. *'प्रलंबित नागरी समस्या'*, *'पाणी व रस्ते तक्रारी'*)\n" .
                       "5. 👥 **कार्यकर्ते व सर्व्हे:** (उदा. *'सक्रिय कार्यकर्ते'*, *'Priya Kadam'*)\n" .
                       "6. 📋 **सर्व्हे मंजुरी:** (उदा. *'तपासणीसाठी प्रलंबित सर्व्हे नोंदी'*)\n" .
                       "7. 🏛️ **शासकीय योजना:** (उदा. *'लाडकी बहीण योजना व लाभार्थी'*)\n" .
                       "8. 📢 **प्रचार घोषणा व भाषण:** (उदा. *'प्रचार घोषणा'*, *'भाषण मुद्दे'*, *'आचारसंहिता'*)\n\n" .
                       "💡 खालीलपैकी कोणत्याही पर्यायावर क्लिक करा किंवा आपला प्रश्न लिहा!";
            } else {
                return "🤖 **I can provide verified live database facts and campaign intelligence on:**\n" .
                       "1. 🏘️ **Village Demographics:** (e.g. *'Madhavnagar population & voters'*, *'Villages list'*)\n" .
                       "2. 📊 **Voters & Sentiment:** (e.g. *'Total voters summary'*, *'Favorable voters'*)\n" .
                       "3. 🗳️ **Booth Progress:** (e.g. *'Booth 1 details'*, *'Top covered booths'*)\n" .
                       "4. ⚠️ **Civic Issues:** (e.g. *'Pending grievances'*, *'Water and road complaints'*)\n" .
                       "5. 👥 **Field Workers:** (e.g. *'Active karyakartas'*, *'Priya Kadam'*)\n" .
                       "6. 📋 **Survey Approvals:** (e.g. *'Pending survey submissions'*)\n" .
                       "7. 🏛️ **Welfare Schemes:** (e.g. *'Government scheme beneficiaries'*)\n" .
                       "8. 📢 **Campaign Strategy:** (e.g. *'Campaign slogans'*, *'Rally speech outline'*, *'Code of conduct'*)\n\n" .
                       "💡 Tap a suggestion below or type your question!";
            }
        }

        // 15. General Overview / Safe Default Database Summary
        $totCitizens = $d['citizens_total'] ?? DB::table('neta_citizens')->count();
        $anukul = $d['sentiment_anukul'] ?? DB::table('neta_citizens')->where('sentiment', 'anukul')->count();
        $totBooths = $d['total_booths'] ?? DB::table('neta_booth_progress')->count();
        $totWorkers = $d['total_karyakartas'] ?? DB::table('neta_karyakartas')->count();
        $pendIssues = $d['pending_issues'] ?? DB::table('neta_crm_issues')->where('status', 'PENDING')->count();
        $totProjects = $d['total_projects'] ?? DB::table('projects')->count();

        if ($isMr) {
            return "📊 **मोहीम सद्यस्थिती:**\n• एकूण मतदार: **{$totCitizens}** (अनुकूल: **{$anukul}**)\n• सक्रिय बूथ: **{$totBooths}**\n• कार्यकर्ते: **{$totWorkers}**\n• नागरी समस्या: **{$pendIssues}** प्रलंबित\n• चालू प्रकल्प: **{$totProjects}**";
        } else {
            return "📊 **Campaign Summary:**\n• Voters: **{$totCitizens}** ({$anukul} favorable)\n• Booths: **{$totBooths}**\n• Workers: **{$totWorkers}**\n• Pending Issues: **{$pendIssues}**\n• Projects: **{$totProjects}**";
        }
    }

    /**
     * Calls Gemini API (Model: gemini-1.5-flash).
     * 1st check DB facts when matched, then check other side (general political/campaign help) if outside DB.
     */
    private function queryGeminiApi(string $userPrompt, array $contextData, bool $isMr, bool $hasDbMatch): ?string
    {
        $apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');
        if (!$apiKey) return null;

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
            $langInstruction = $isMr 
                ? "Reply strictly in simple, natural Marathi (मराठी)." 
                : "Reply strictly in simple, natural English.";

            if ($hasDbMatch) {
                // Priority 1: Grounded in verified MySQL database facts
                $factsJson = json_encode($contextData['data'], JSON_UNESCAPED_UNICODE);
                $systemPrompt = "You are the AI Campaign Assistant for a Maharashtra election management system.\n" .
                    "VERIFIED DATABASE FACTS:\n{$factsJson}\n\n" .
                    "{$langInstruction}\n\n" .
                    "STRICT RULES FOR SMS-STYLE RESPONSE (NON-TECHNICAL USER):\n" .
                    "1. Answer using ONLY the verified database facts above.\n" .
                    "2. Keep your response VERY SIMPLE, SHORT, and CRISP (maximum 2 to 4 bullet points or short lines, SMS-style).\n" .
                    "3. NEVER mention technical jargon like 'MySQL', 'database table', 'records', 'JSON', or 'HQ'.\n" .
                    "4. Use simple emojis at line beginnings (e.g. 📊, 🗳️, 👥, ⚠️, 👤).\n" .
                    "5. No long introductions or conclusions. Give the exact numbers directly.";
            } else {
                // Priority 2: Outside database ("Check other side" - Campaign strategy, slogans, speeches, advice)
                $systemPrompt = "You are the expert AI Political Campaign & Strategy Advisor for Maharashtra elections.\n" .
                    "The user is asking for campaign strategy, slogans, speech points, rally planning, door-to-door tips, code of conduct, or general advice (outside the local database records).\n\n" .
                    "{$langInstruction}\n\n" .
                    "STRICT RULES FOR SMS-STYLE RESPONSE (NON-TECHNICAL USER):\n" .
                    "1. Provide practical, high-impact, actionable guidance directly.\n" .
                    "2. Keep your response VERY SIMPLE, SHORT, and CRISP (maximum 2 to 4 short bullet points, SMS-style).\n" .
                    "3. Absolutely NO long essays, NO markdown headers (# or ##), and NO heavy academic words.\n" .
                    "4. Use motivating, field-friendly tone with emojis (e.g. 📢, 🎯, 💡, 🤝).\n" .
                    "5. Easy to read in 5 seconds on a mobile screen.";
            }

            $res = Http::withoutVerifying()->timeout(8)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser Question: " . $userPrompt],
                        ],
                    ],
                ],
            ]);

            if ($res->successful()) {
                $candidates = $res->json('candidates');
                if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                    return $candidates[0]['content']['parts'][0]['text'];
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback to internal smart query engine
        }

        return null;
    }
}
