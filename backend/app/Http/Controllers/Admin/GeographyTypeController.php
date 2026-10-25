<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeographyType;
use Illuminate\Http\JsonResponse;

class GeographyTypeController extends Controller
{
    /**
     * GET /api/admin/geography-types
     * List all reference geography types ordered by hierarchy level.
     */
    public function index(): JsonResponse
    {
        $types = GeographyType::orderBy('level_order', 'asc')->get();

        return response()->json([
            'data' => $types->map(fn (GeographyType $type) => [
                'id'           => $type->id,
                'name'         => $type->name,
                'display_name' => $type->display_name,
                'level_order'  => (int) $type->level_order,
            ]),
        ]);
    }
}
