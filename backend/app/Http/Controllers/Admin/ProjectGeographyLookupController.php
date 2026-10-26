<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectGeographyLookupController extends Controller
{
    public function districts(Request $request): JsonResponse
    {
        $term = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $query = DB::table('districts')
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr']);

        if (! empty($term)) {
            $query->where(fn ($q) => $q
                ->where('district_name', 'like', "%{$term}%")
                ->orWhere('district_name_mr', 'like', "%{$term}%"));
        }

        $data = $query->orderBy('district_name')->get();

        return response()->json(['data' => $data]);
    }

    public function lokSabha(Request $request): JsonResponse
    {
        $term = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $query = DB::table('lok_sabha_vidhan_sabha')
            ->select([
                'lok_sabha_no as code',
                'lok_sabha_name as name_en',
                DB::raw('MAX(lok_sabha_name_mr) as name_mr'),
            ]);

        if (! empty($term)) {
            $query->where(fn ($q) => $q
                ->where('lok_sabha_name', 'like', "%{$term}%")
                ->orWhere('lok_sabha_name_mr', 'like', "%{$term}%"));
        }

        $data = $query->groupBy('lok_sabha_no', 'lok_sabha_name')
            ->orderBy('lok_sabha_no')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function assembliesForLokSabha(Request $request, int $lokSabhaNo): JsonResponse
    {
        $query = DB::table('lok_sabha_vidhan_sabha as mapping')
            ->join('vidhan_sabha as assemblies', 'assemblies.name', '=', 'mapping.vidhan_sabha_name')
            ->where('mapping.lok_sabha_no', $lokSabhaNo)
            ->select([
                'assemblies.id as code',
                'assemblies.name as name_en',
                DB::raw('MAX(mapping.vidhan_sabha_name_mr) as name_mr'),
            ])
            ->groupBy('assemblies.id', 'assemblies.name')
            ->orderBy('assemblies.id');

        if ($request->filled('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where('assemblies.name', 'like', $term);
        }

        $districts = DB::table('districts')
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->get()
            ->keyBy('code');

        $data = $query->get()->map(function ($assembly) use ($districts) {
            $districtCode = self::getDistrictCodeForAssembly((int) $assembly->code);
            $dist = $districtCode ? $districts->get($districtCode) : null;
            return [
                'code' => $assembly->code,
                'name_en' => $assembly->name_en,
                'name_mr' => $assembly->name_mr,
                'district_code' => $districtCode,
                'district_name' => $dist?->name_en,
                'district_name_mr' => $dist?->name_mr,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public static function getDistrictCodeForAssembly(int $assemblyId): ?string
    {
        if ($assemblyId >= 1 && $assemblyId <= 4) return '486'; // Nandurbar
        if ($assemblyId >= 5 && $assemblyId <= 9) return '474'; // Dhule
        if ($assemblyId >= 10 && $assemblyId <= 20) return '478'; // Jalgaon
        if ($assemblyId >= 21 && $assemblyId <= 27) return '472'; // Buldhana
        if ($assemblyId >= 28 && $assemblyId <= 32) return '467'; // Akola
        if ($assemblyId >= 33 && $assemblyId <= 35) return '499'; // Washim
        if ($assemblyId >= 36 && $assemblyId <= 43) return '468'; // Amravati
        if ($assemblyId >= 44 && $assemblyId <= 47) return '498'; // Wardha
        if ($assemblyId >= 48 && $assemblyId <= 59) return '484'; // Nagpur
        if ($assemblyId >= 60 && $assemblyId <= 62) return '471'; // Bhandara
        if ($assemblyId >= 63 && $assemblyId <= 66) return '476'; // Gondia
        if ($assemblyId >= 67 && $assemblyId <= 69) return '475'; // Gadchiroli
        if ($assemblyId >= 70 && $assemblyId <= 75) return '473'; // Chandrapur
        if ($assemblyId >= 76 && $assemblyId <= 82) return '500'; // Yavatmal
        if ($assemblyId >= 83 && $assemblyId <= 91) return '485'; // Nanded
        if ($assemblyId >= 92 && $assemblyId <= 94) return '477'; // Hingoli
        if ($assemblyId >= 95 && $assemblyId <= 98) return '489'; // Parbhani
        if ($assemblyId >= 99 && $assemblyId <= 103) return '479'; // Jalna
        if ($assemblyId >= 104 && $assemblyId <= 112) return '469'; // Chhatrapati Sambhajinagar
        if ($assemblyId >= 113 && $assemblyId <= 127) return '487'; // Nashik
        if ($assemblyId >= 128 && $assemblyId <= 133) return '665'; // Palghar
        if ($assemblyId >= 134 && $assemblyId <= 151) return '497'; // Thane
        if ($assemblyId >= 152 && $assemblyId <= 177) return '483'; // Mumbai Suburban
        if ($assemblyId >= 178 && $assemblyId <= 187) return '482'; // Mumbai
        if ($assemblyId >= 188 && $assemblyId <= 194) return '491'; // Raigad
        if ($assemblyId >= 195 && $assemblyId <= 215) return '490'; // Pune
        if ($assemblyId >= 216 && $assemblyId <= 227) return '466'; // Ahilyanagar
        if ($assemblyId >= 228 && $assemblyId <= 233) return '470'; // Beed
        if ($assemblyId >= 234 && $assemblyId <= 239) return '481'; // Latur
        if ($assemblyId >= 240 && $assemblyId <= 243) return '488'; // Dharashiv
        if ($assemblyId >= 244 && $assemblyId <= 254) return '496'; // Solapur
        if ($assemblyId >= 255 && $assemblyId <= 262) return '494'; // Satara
        if ($assemblyId >= 263 && $assemblyId <= 267) return '492'; // Ratnagiri
        if ($assemblyId >= 268 && $assemblyId <= 270) return '495'; // Sindhudurg
        if ($assemblyId >= 271 && $assemblyId <= 280) return '480'; // Kolhapur
        if ($assemblyId >= 281 && $assemblyId <= 288) return '493'; // Sangli
        return null;
    }

    public function districtsForLokSabha(Request $request, int $lokSabhaNo): JsonResponse
    {
        $query = DB::table('lok_sabha_vidhan_sabha as m')
            ->join('vidhan_sabha as v', 'v.name', '=', 'm.vidhan_sabha_name')
            ->where('m.lok_sabha_no', $lokSabhaNo);

        $assemblyNos = $request->query('assembly_nos');
        if (! empty($assemblyNos)) {
            if (is_string($assemblyNos)) {
                $assemblyNos = array_filter(array_map('trim', explode(',', $assemblyNos)));
            }
            if (is_array($assemblyNos) && count($assemblyNos) > 0) {
                $query->whereIn('v.id', array_map('intval', $assemblyNos));
            }
        } elseif ($request->filled('assembly_no')) {
            $query->where('v.id', (int) $request->query('assembly_no'));
        }

        $asmIds = $query->pluck('v.id')->all();

        $districtCodes = array_values(array_unique(array_filter(array_map([self::class, 'getDistrictCodeForAssembly'], $asmIds))));

        if (! empty($districtCodes)) {
            $districts = DB::table('districts')
                ->whereIn('district_code', $districtCodes)
                ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
                ->orderBy('district_name')
                ->get()
                ->map(function ($d) use ($asmIds) {
                    $matching = array_filter($asmIds, fn ($id) => self::getDistrictCodeForAssembly((int) $id) === (string) $d->code);
                    return [
                        'code' => (string) $d->code,
                        'name_en' => $d->name_en,
                        'name_mr' => $d->name_mr,
                        'assembly_count' => count($matching),
                    ];
                });

            if ($districts->isNotEmpty()) {
                return response()->json(['data' => $districts]);
            }
        }

        $mapping = DB::table('lok_sabha_vidhan_sabha')->where('lok_sabha_no', $lokSabhaNo);
        $relatedNames = $mapping->pluck('vidhan_sabha_name')->all();
        $lokSabhaName = $mapping->value('lok_sabha_name');
        if ($lokSabhaName) $relatedNames[] = $lokSabhaName;
        $districts = DB::table('districts')
            ->whereIn('district_name', array_unique($relatedNames))
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->orderBy('district_name')
            ->get();

        return response()->json(['data' => $districts]);
    }

    public function districtsForAssembly(int $assemblyCode): JsonResponse
    {
        $districtCode = self::getDistrictCodeForAssembly($assemblyCode);
        if ($districtCode) {
            $districts = DB::table('districts')
                ->where('district_code', $districtCode)
                ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
                ->get();
            if ($districts->isNotEmpty()) {
                return response()->json(['data' => $districts]);
            }
        }

        $assemblyName = DB::table('vidhan_sabha')->where('id', $assemblyCode)->value('name');
        if (! $assemblyName) {
            return response()->json(['data' => []]);
        }

        $districts = DB::table('districts')
            ->where('district_name', $assemblyName)
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->get();

        return response()->json(['data' => $districts]);
    }

    public function talukasForAssembly(Request $request, int $assemblyCode): JsonResponse
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $assemblyName = DB::table('vidhan_sabha')->where('id', $assemblyCode)->value('name');
        if (! $assemblyName) return response()->json(['data' => []]);

        $talukas = $this->talukasMappedToAssembly($assemblyName);
        if ($talukas->isEmpty()) {
            $districtCode = DB::table('districts')->where('district_name', $assemblyName)->value('district_code');
            if ($districtCode) {
                $talukas = DB::table('talukas')->where('district_code', $districtCode)->get(['district_code', 'taluka_code', 'taluka_name']);
            }
        }

        if (Schema::hasColumn('polling_stations', 'assembly_no')) {
            $mappedCodes = DB::table('polling_stations')->where('assembly_no', $assemblyCode)->whereNotNull('taluka_code')->distinct()->pluck('taluka_code');
            if ($mappedCodes->isNotEmpty()) {
                $fromStations = DB::table('talukas')->whereIn('taluka_code', $mappedCodes)->get(['district_code', 'taluka_code', 'taluka_name']);
                $talukas = $talukas->merge($fromStations)->unique('taluka_code')->values();
            }
        }

        if (! empty($validated['search'])) {
            $term = mb_strtolower($validated['search']);
            $talukas = $talukas->filter(fn ($taluka) => str_contains(mb_strtolower($taluka->taluka_name), $term))->values();
        }

        $data = $talukas->map(fn ($taluka) => [
            'code' => (string) $taluka->taluka_code,
            'name_en' => $taluka->taluka_name,
            'district_code' => (string) $taluka->district_code,
        ])->values();

        return response()->json(['data' => $data]);
    }

    private function talukasMappedToAssembly(string $assemblyName)
    {
        $normalize = fn (string $value) => preg_replace('/[^a-z0-9]/', '', mb_strtolower($value));
        $assembly = $normalize($assemblyName);
        $parts = collect(preg_split('/[^a-z0-9]+/i', mb_strtolower($assemblyName), -1, PREG_SPLIT_NO_EMPTY))
            ->map($normalize)->all();
        return DB::table('talukas')->get(['district_code', 'taluka_code', 'taluka_name'])
            ->filter(function ($taluka) use ($normalize, $assembly, $parts) {
                $name = $normalize($taluka->taluka_name);
                return $name !== '' && ($name === $assembly || in_array($name, $parts, true) || (mb_strlen($name) >= 5 && str_contains($assembly, $name)));
            })->values();
    }

    public function assemblies(Request $request): JsonResponse
    {
        $term = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $localizedNames = DB::table('lok_sabha_vidhan_sabha')
            ->select('vidhan_sabha_name')
            ->selectRaw('MAX(vidhan_sabha_name_mr) as name_mr')
            ->groupBy('vidhan_sabha_name');

        $query = DB::table('vidhan_sabha as assemblies')
            ->leftJoinSub($localizedNames, 'localized', 'localized.vidhan_sabha_name', '=', 'assemblies.name')
            ->select([
                'assemblies.id as code',
                'assemblies.name as name_en',
                'localized.name_mr',
            ]);

        if (! empty($term)) {
            $query->where(fn ($q) => $q
                ->where('assemblies.name', 'like', "%{$term}%")
                ->orWhere('localized.name_mr', 'like', "%{$term}%"));
        }

        $data = $query->orderBy('assemblies.id')->get();

        return response()->json(['data' => $data]);
    }

    public function pollingStations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_code' => ['nullable', 'string', 'max:30'],
            'district_codes' => ['nullable'],
            'assembly_no' => ['nullable', 'integer', 'min:1'],
            'assembly_nos' => ['nullable'],
            'taluka_code' => ['nullable', 'string', 'max:30'],
            'village_code' => ['nullable', 'string', 'max:30'],
            'taluka_codes' => ['nullable'],
            'village_codes' => ['nullable'],
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'in:local_bodies,polling_stations'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        $districtCodes = collect();
        if ($request->filled('district_code')) {
            $val = $request->input('district_code');
            $districtCodes = $districtCodes->merge(is_array($val) ? $val : explode(',', (string) $val));
        }
        if ($request->filled('district_codes')) {
            $val = $request->input('district_codes');
            $districtCodes = $districtCodes->merge(is_array($val) ? $val : explode(',', (string) $val));
        }
        $districtCodes = $districtCodes->map(fn ($c) => trim((string) $c))->filter()->unique()->values();

        $talukaCodes = collect();
        if ($request->filled('taluka_code')) {
            $val = $request->input('taluka_code');
            $talukaCodes = $talukaCodes->merge(is_array($val) ? $val : explode(',', (string) $val));
        }
        if ($request->filled('taluka_codes')) {
            $val = $request->input('taluka_codes');
            $talukaCodes = $talukaCodes->merge(is_array($val) ? $val : explode(',', (string) $val));
        }
        $talukaCodes = $talukaCodes->map(fn ($c) => trim((string) $c))->filter()->unique()->values();

        $assemblyNos = collect();
        if ($request->filled('assembly_no')) {
            $assemblyNos->push((int) $request->input('assembly_no'));
        }
        if ($request->filled('assembly_nos')) {
            $nos = $request->input('assembly_nos');
            $assemblyNos = $assemblyNos->merge(is_array($nos) ? $nos : explode(',', (string) $nos));
        }
        $assemblyNos = $assemblyNos->map(fn ($n) => (int) $n)->filter()->unique()->values();

        // If talukaCodes is empty, but assemblies are given, map them if possible
        if ($talukaCodes->isEmpty() && $assemblyNos->isNotEmpty()) {
            $assemblyNames = DB::table('vidhan_sabha')->whereIn('id', $assemblyNos)->pluck('name');
            $mappedTalukaCodes = collect();
            foreach ($assemblyNames as $asmName) {
                $mappedTalukas = $this->talukasMappedToAssembly($asmName);
                $mappedTalukaCodes = $mappedTalukaCodes->merge($mappedTalukas->pluck('taluka_code'));
            }
            if ($mappedTalukaCodes->isNotEmpty()) {
                $talukaCodes = $mappedTalukaCodes->unique()->values();
            }
        }

        // If districtCodes is empty, but assembly is given, find its district
        if ($districtCodes->isEmpty() && $assemblyNos->isNotEmpty()) {
            $dCodes = collect();
            foreach ($assemblyNos as $aNo) {
                $d = self::getDistrictCodeForAssembly($aNo);
                if ($d) $dCodes->push((string) $d);
            }
            $districtCodes = $dCodes->unique()->values();
        }

        $source = $request->query('source');
        $hasPollingStations = Schema::hasTable('polling_stations') && DB::table('polling_stations')->exists();

        // Query polling_stations only if explicitly requested AND polling_stations has records
        if ($source === 'polling_stations' && $hasPollingStations) {
            $query = DB::table('polling_stations');
            if ($districtCodes->isNotEmpty()) {
                $query->whereIn('district_code', $districtCodes);
            }
            if ($assemblyNos->isNotEmpty()) {
                $query->whereIn('assembly_no', $assemblyNos);
            }
            if ($talukaCodes->isNotEmpty() && Schema::hasColumn('polling_stations', 'taluka_code')) {
                $query->whereIn('taluka_code', $talukaCodes);
            }
            if ($request->filled('village_code') && Schema::hasColumn('polling_stations', 'village_code')) {
                $query->where('village_code', $request->input('village_code'));
            }
            if ($request->filled('village_codes') && Schema::hasColumn('polling_stations', 'village_code')) {
                $vCodes = is_array($request->input('village_codes')) ? $request->input('village_codes') : explode(',', (string) $request->input('village_codes'));
                $query->whereIn('village_code', $vCodes);
            }

            $latestYear = (clone $query)->max('election_year');
            if ($latestYear) {
                $query->where('election_year', $latestYear);
            }

            if (! empty($validated['search'])) {
                $term = $validated['search'];
                $query->where(fn ($builder) => $builder
                    ->where('part_no', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('name_mr', 'like', "%{$term}%"));
            }

            $limit = min((int) $request->input('per_page', $request->input('limit', 1000)), 5000);
            $data = $query
                ->orderByRaw('CAST(part_no AS UNSIGNED)')
                ->limit($limit)
                ->get([
                    'id as code', 'election_year', 'part_no',
                    'name_en', 'name_mr', 'district_code', 'assembly_no',
                    'taluka_code', 'village_code',
                ]);

            return response()->json(['data' => $data]);
        }

        // By default, query from local_bodies table
        $query = DB::table('local_bodies');
        if ($districtCodes->isNotEmpty()) {
            $query->whereIn('district_code', $districtCodes);
        }
        if ($talukaCodes->isNotEmpty()) {
            $query->whereIn('parent_code', $talukaCodes);
        }

        if (! empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(function ($builder) use ($term) {
                $builder->where('local_body_name', 'like', "%{$term}%")
                    ->orWhere('local_body_code', 'like', "%{$term}%");
            });
        }

        $limit = min((int) $request->input('per_page', $request->input('limit', 2000)), 5000);

        $typeNames = [
            'G' => 'Gram Panchayat',
            'M' => 'Municipal Corporation',
            'C' => 'Municipal Council',
            'N' => 'Nagar Panchayat',
            'B' => 'Block Panchayat',
        ];

        $data = $query
            ->orderBy('local_body_name')
            ->limit($limit)
            ->get([
                'id',
                'district_code',
                'parent_code',
                'local_body_code',
                'local_body_name',
                'local_body_type',
            ])
            ->map(function ($lb) use ($typeNames) {
                $typeLabel = $typeNames[$lb->local_body_type] ?? 'Local Body';
                return [
                    'code' => (string) $lb->local_body_code,
                    'name_en' => $lb->local_body_name,
                    'name' => $lb->local_body_name,
                    'name_mr' => null,
                    'part_no' => null,
                    'local_body_code' => (string) $lb->local_body_code,
                    'local_body_name' => $lb->local_body_name,
                    'local_body_type' => $lb->local_body_type,
                    'type_label' => $typeLabel,
                    'district_code' => (string) $lb->district_code,
                    'taluka_code' => (string) $lb->parent_code,
                ];
            });

        return response()->json(['data' => $data]);
    }

    public const GRADUATE_CONSTITUENCIES = [
        [
            'code' => 'GRAD_MUMBAI',
            'name_en' => 'Mumbai Graduate Constituency',
            'name_mr' => 'मुंबई पदवीधर मतदारसंघ',
            'district_codes' => ['482', '483'],
        ],
        [
            'code' => 'GRAD_KONKAN',
            'name_en' => 'Konkan Division Graduate Constituency',
            'name_mr' => 'कोकण विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['497', '665', '491', '492', '495'],
        ],
        [
            'code' => 'GRAD_PUNE',
            'name_en' => 'Pune Division Graduate Constituency',
            'name_mr' => 'पुणे विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['490', '494', '493', '480', '496'],
        ],
        [
            'code' => 'GRAD_NASHIK',
            'name_en' => 'Nashik Division Graduate Constituency',
            'name_mr' => 'नाशिक विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['487', '466', '478', '474', '486'],
        ],
        [
            'code' => 'GRAD_AURANGABAD',
            'name_en' => 'Chhatrapati Sambhajinagar (Aurangabad) Division Graduate Constituency',
            'name_mr' => 'छत्रपती संभाजीनगर (औरंगाबाद) विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['469', '479', '470', '489', '485', '488', '481', '477'],
        ],
        [
            'code' => 'GRAD_NAGPUR',
            'name_en' => 'Nagpur Division Graduate Constituency',
            'name_mr' => 'नागपूर विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['484', '498', '471', '476', '473', '475'],
        ],
        [
            'code' => 'GRAD_AMRAVATI',
            'name_en' => 'Amravati Division Graduate Constituency',
            'name_mr' => 'अमरावती विभाग पदवीधर मतदारसंघ',
            'district_codes' => ['468', '467', '472', '499', '500'],
        ],
    ];

    public const TEACHER_CONSTITUENCIES = [
        [
            'code' => 'TCH_MUMBAI',
            'name_en' => 'Mumbai Teacher Constituency',
            'name_mr' => 'मुंबई शिक्षक मतदारसंघ',
            'district_codes' => ['482', '483'],
        ],
        [
            'code' => 'TCH_KONKAN',
            'name_en' => 'Konkan Division Teacher Constituency',
            'name_mr' => 'कोकण विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['497', '665', '491', '492', '495'],
        ],
        [
            'code' => 'TCH_PUNE',
            'name_en' => 'Pune Division Teacher Constituency',
            'name_mr' => 'पुणे विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['490', '494', '493', '480', '496'],
        ],
        [
            'code' => 'TCH_NASHIK',
            'name_en' => 'Nashik Division Teacher Constituency',
            'name_mr' => 'नाशिक विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['487', '466', '478', '474', '486'],
        ],
        [
            'code' => 'TCH_AURANGABAD',
            'name_en' => 'Chhatrapati Sambhajinagar (Aurangabad) Division Teacher Constituency',
            'name_mr' => 'छत्रपती संभाजीनगर (औरंगाबाद) विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['469', '479', '470', '489', '485', '488', '481', '477'],
        ],
        [
            'code' => 'TCH_NAGPUR',
            'name_en' => 'Nagpur Division Teacher Constituency',
            'name_mr' => 'नागपूर विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['484', '498', '471', '476', '473', '475'],
        ],
        [
            'code' => 'TCH_AMRAVATI',
            'name_en' => 'Amravati Division Teacher Constituency',
            'name_mr' => 'अमरावती विभाग शिक्षक मतदारसंघ',
            'district_codes' => ['468', '467', '472', '499', '500'],
        ],
    ];

    public function graduateConstituencies(Request $request): JsonResponse
    {
        $term = mb_strtolower($request->query('search', ''));
        $items = collect(self::GRADUATE_CONSTITUENCIES);
        if ($term !== '') {
            $items = $items->filter(fn ($c) =>
                str_contains(mb_strtolower($c['name_en']), $term) ||
                str_contains(mb_strtolower($c['name_mr']), $term) ||
                str_contains(mb_strtolower($c['code']), $term)
            );
        }
        return response()->json(['data' => $items->values()->all()]);
    }

    public function districtsForGraduateConstituency(string $code): JsonResponse
    {
        $matched = collect(self::GRADUATE_CONSTITUENCIES)->firstWhere('code', $code);
        if (! $matched) {
            return response()->json(['data' => []]);
        }

        $districts = DB::table('districts')
            ->whereIn('district_code', $matched['district_codes'])
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->orderBy('district_name')
            ->get();

        return response()->json(['data' => $districts]);
    }

    public function teacherConstituencies(Request $request): JsonResponse
    {
        $term = mb_strtolower($request->query('search', ''));
        $items = collect(self::TEACHER_CONSTITUENCIES);
        if ($term !== '') {
            $items = $items->filter(fn ($c) =>
                str_contains(mb_strtolower($c['name_en']), $term) ||
                str_contains(mb_strtolower($c['name_mr']), $term) ||
                str_contains(mb_strtolower($c['code']), $term)
            );
        }
        return response()->json(['data' => $items->values()->all()]);
    }

    public function districtsForTeacherConstituency(string $code): JsonResponse
    {
        $matched = collect(self::TEACHER_CONSTITUENCIES)->firstWhere('code', $code);
        if (! $matched) {
            return response()->json(['data' => []]);
        }

        $districts = DB::table('districts')
            ->whereIn('district_code', $matched['district_codes'])
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->orderBy('district_name')
            ->get();

        return response()->json(['data' => $districts]);
    }

    public const LOCAL_AUTHORITIES_CONSTITUENCIES = [
        [
            'code' => 'LOC_SANGLI_SATARA',
            'name_en' => 'Sangli-Satara Local Authorities Constituency',
            'name_mr' => 'सांगली-सातारा स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['493', '494'],
        ],
        [
            'code' => 'LOC_KOLHAPUR',
            'name_en' => 'Kolhapur Local Authorities Constituency',
            'name_mr' => 'कोल्हापूर स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['480'],
        ],
        [
            'code' => 'LOC_PUNE',
            'name_en' => 'Pune Local Authorities Constituency',
            'name_mr' => 'पुणे स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['490'],
        ],
        [
            'code' => 'LOC_SOLAPUR',
            'name_en' => 'Solapur Local Authorities Constituency',
            'name_mr' => 'सोलापूर स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['496'],
        ],
        [
            'code' => 'LOC_MUMBAI',
            'name_en' => 'Mumbai Local Authorities Constituency (2 Seats)',
            'name_mr' => 'मुंबई स्थानिक प्राधिकारी मतदारसंघ (२ जागा)',
            'district_codes' => ['482', '483'],
        ],
        [
            'code' => 'LOC_THANE_PALGHAR',
            'name_en' => 'Thane-Palghar Local Authorities Constituency',
            'name_mr' => 'ठाणे-पालघर स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['497', '665'],
        ],
        [
            'code' => 'LOC_RAIGAD_RATNAGIRI_SINDHUDURG',
            'name_en' => 'Konkan (Raigad-Ratnagiri-Sindhudurg) Local Authorities Constituency',
            'name_mr' => 'कोकण (रायगड-रत्नागिरी-सिंधुदुर्ग) स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['491', '492', '495'],
        ],
        [
            'code' => 'LOC_NASHIK',
            'name_en' => 'Nashik Local Authorities Constituency',
            'name_mr' => 'नाशिक स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['487'],
        ],
        [
            'code' => 'LOC_DHULE_NANDURBAR',
            'name_en' => 'Dhule-Nandurbar Local Authorities Constituency',
            'name_mr' => 'धुळे-नंदुरबार स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['474', '486'],
        ],
        [
            'code' => 'LOC_JALGAON',
            'name_en' => 'Jalgaon Local Authorities Constituency',
            'name_mr' => 'जळगाव स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['478'],
        ],
        [
            'code' => 'LOC_AHMEDNAGAR',
            'name_en' => 'Ahilyanagar (Ahmednagar) Local Authorities Constituency',
            'name_mr' => 'अहिल्यानगर (अहमदनगर) स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['466'],
        ],
        [
            'code' => 'LOC_AURANGABAD_JALNA',
            'name_en' => 'Chhatrapati Sambhajinagar-Jalna Local Authorities Constituency',
            'name_mr' => 'छत्रपती संभाजीनगर-जालना स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['469', '479'],
        ],
        [
            'code' => 'LOC_BEED_OSMANABAD_LATUR',
            'name_en' => 'Beed-Dharashiv-Latur Local Authorities Constituency',
            'name_mr' => 'बीड-धाराशिव-लातूर स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['470', '489', '481'],
        ],
        [
            'code' => 'LOC_NANDED',
            'name_en' => 'Nanded Local Authorities Constituency',
            'name_mr' => 'नांदेड स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['485'],
        ],
        [
            'code' => 'LOC_PARBHANI_HINGOLI',
            'name_en' => 'Parbhani-Hingoli Local Authorities Constituency',
            'name_mr' => 'परभणी-हिंगोली स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['488', '477'],
        ],
        [
            'code' => 'LOC_AMRAVATI',
            'name_en' => 'Amravati Local Authorities Constituency',
            'name_mr' => 'अमरावती स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['468'],
        ],
        [
            'code' => 'LOC_AKOLA_WASHIM_BULDHANA',
            'name_en' => 'Akola-Washim-Buldhana Local Authorities Constituency',
            'name_mr' => 'अकोला-वाशिम-बुलढाणा स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['467', '499', '472'],
        ],
        [
            'code' => 'LOC_YAVATMAL',
            'name_en' => 'Yavatmal Local Authorities Constituency',
            'name_mr' => 'यवतमाळ स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['500'],
        ],
        [
            'code' => 'LOC_NAGPUR',
            'name_en' => 'Nagpur Local Authorities Constituency',
            'name_mr' => 'नागपूर स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['484'],
        ],
        [
            'code' => 'LOC_WARDHA_CHANDRAPUR_GADCHIROLI',
            'name_en' => 'Wardha-Chandrapur-Gadchiroli Local Authorities Constituency',
            'name_mr' => 'वर्धा-चंद्रपूर-गडचिरोली स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['498', '473', '476'],
        ],
        [
            'code' => 'LOC_BHANDARA_GONDIA',
            'name_en' => 'Bhandara-Gondia Local Authorities Constituency',
            'name_mr' => 'भंडारा-गोंदिया स्थानिक प्राधिकारी मतदारसंघ',
            'district_codes' => ['471', '475'],
        ],
    ];

    public function localAuthoritiesConstituencies(Request $request): JsonResponse
    {
        $term = mb_strtolower($request->query('search', ''));
        $items = collect(self::LOCAL_AUTHORITIES_CONSTITUENCIES);
        if ($term !== '') {
            $items = $items->filter(fn ($c) =>
                str_contains(mb_strtolower($c['name_en']), $term) ||
                str_contains(mb_strtolower($c['name_mr']), $term) ||
                str_contains(mb_strtolower($c['code']), $term)
            );
        }
        return response()->json(['data' => $items->values()->all()]);
    }

    public function districtsForLocalAuthoritiesConstituency(string $code): JsonResponse
    {
        $matched = collect(self::LOCAL_AUTHORITIES_CONSTITUENCIES)->firstWhere('code', $code);
        if (! $matched) {
            return response()->json(['data' => []]);
        }

        $districts = DB::table('districts')
            ->whereIn('district_code', $matched['district_codes'])
            ->select(['district_code as code', 'district_name as name_en', 'district_name_mr as name_mr'])
            ->orderBy('district_name')
            ->get();

        return response()->json(['data' => $districts]);
    }
}

