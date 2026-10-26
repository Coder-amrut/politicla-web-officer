<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only endpoints for the shared location master tables used by frontend
 * selectors. Child resources accept their parent code so the UI can load only
 * the relevant districts, talukas, villages, or local bodies.
 */
class MasterDataController extends Controller
{
    /**
     * GET /api/master-data/states
     * Return available states. All columns are returned to preserve optional
     * localized state-name columns if they exist in the imported table.
     */
    public function states(Request $request): JsonResponse
    {
        return $this->paginated(
            $request,
            DB::table('states')->orderBy('state_code'),
        );
    }

    /**
     * GET /api/master-data/districts
     * Return district codes and names for the frontend's district selector.
     */
    public function districts(Request $request): JsonResponse
    {
        return $this->paginated(
            $request,
            DB::table('districts')
                ->select([
                    'district_code',
                    'district_code as code',
                    'district_name',
                    'district_name as name',
                    'district_name as name_en',
                ])
                ->orderBy('district_name'),
        );
    }

    /**
     * GET /api/master-data/talukas?district_code=466
     * Filter talukas by district so the frontend can populate the next selector.
     */
    public function talukas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_code' => ['nullable'],
            'district_codes' => ['nullable'],
            'assembly_no' => ['nullable', 'integer', 'min:1'],
            'assembly_nos' => ['nullable', 'array'],
            'assembly_nos.*' => ['integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = DB::table('talukas')
            ->select([
                'district_code',
                'taluka_code',
                'taluka_code as code',
                'taluka_name',
                'taluka_name as name',
                'taluka_name as name_en',
            ])
            ->orderBy('taluka_name');

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

        if ($districtCodes->isNotEmpty()) {
            $query->whereIn('district_code', $districtCodes);
        }

        if (! empty($validated['search'])) {
            $query->where('taluka_name', 'like', '%' . $validated['search'] . '%');
        }
        $assemblyNumbers = $validated['assembly_nos'] ?? (isset($validated['assembly_no']) ? [$validated['assembly_no']] : []);
        if ($assemblyNumbers && DB::table('polling_stations')->whereIn('assembly_no', $assemblyNumbers)->whereNotNull('taluka_code')->exists()) {
            $talukaCodes = DB::table('polling_stations')->whereIn('assembly_no', $assemblyNumbers)->whereNotNull('taluka_code')->select('taluka_code');
            $query->whereIn('taluka_code', $talukaCodes);
        }

        return $this->paginated($request, $query);
    }

    /**
     * GET /api/master-data/villages?district_code=466&taluka_code=4201
     * Return villages, optionally narrowed by district and taluka codes.
     */
    public function villages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_code'  => ['nullable'],
            'district_codes' => ['nullable'],
            'taluka_code'    => ['nullable'],
            'taluka_codes'   => ['nullable'],
            'assembly_no'    => ['nullable', 'integer', 'min:1'],
            'assembly_nos'   => ['nullable'],
            'search'         => ['nullable', 'string', 'max:100'],
        ]);

        $query = DB::table('villages')
            ->select([
                'id',
                'district_code',
                'taluka_code',
                'village_code',
                'village_code as code',
                'village_name',
                'village_name as name',
                'village_name as name_en',
                'population',
                'total_voters',
            ])
            ->orderBy('village_name');

        // Extract district codes
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

        // Extract taluka codes from taluka_code and/or taluka_codes
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

        if ($talukaCodes->isNotEmpty()) {
            $query->whereIn('taluka_code', $talukaCodes);
        }
        if ($districtCodes->isNotEmpty()) {
            $query->whereIn('district_code', $districtCodes);
        }

        if (! empty($validated['search'])) {
            $term = $validated['search'];
            $query->where(fn ($q) => $q
                ->where('village_name', 'like', "%{$term}%")
                ->orWhere('village_code', 'like', "%{$term}%"));
        }

        return $this->paginated($request, $query);
    }

    /**
     * GET /api/master-data/local-bodies?district_code=466&taluka_code=4201
     * Return local bodies, optionally narrowed by district and taluka codes.
     */
    public function localBodies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_code'  => ['nullable', 'string', 'max:20'],
            'district_codes' => ['nullable'],
            'taluka_code'    => ['nullable', 'string', 'max:20'],
            'taluka_codes'   => ['nullable'],
            'search'         => ['nullable', 'string', 'max:100'],
            'type'           => ['nullable', 'string', 'max:20'],
            'types'          => ['nullable'],
        ]);

        $query = DB::table('local_bodies')
            ->select([
                'id',
                'district_code',
                'parent_code as taluka_code',
                'parent_code',
                'local_body_code as code',
                'local_body_code',
                'local_body_name as name_en',
                'local_body_name',
                'local_body_type',
            ])
            ->orderBy('local_body_name');

        if ($request->filled('type')) {
            $query->where('local_body_type', $request->input('type'));
        } elseif ($request->filled('types')) {
            $typesVal = $request->input('types');
            $types = is_array($typesVal) ? $typesVal : explode(',', (string) $typesVal);
            $query->whereIn('local_body_type', array_filter($types));
        }

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

        if ($districtCodes->isNotEmpty()) {
            $query->whereIn('district_code', $districtCodes);
        }

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

        return $this->paginated($request, $query);
    }

    /**
     * Keep large master lists manageable for the API and frontend. The client
     * can request subsequent pages; per_page is capped to prevent huge reads.
     */
    private function paginated(Request $request, Builder $query): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:5000'],
        ]);

        $defaultPerPage = ($request->filled('taluka_code') || $request->filled('taluka_codes')) ? 2000 : 100;
        $perPage = (int) ($validated['per_page'] ?? $defaultPerPage);
        $records = $query->paginate($perPage);

        return response()->json([
            'data'         => $records->items(),
            'total'        => $records->total(),
            'per_page'     => $records->perPage(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
        ]);
    }
}
