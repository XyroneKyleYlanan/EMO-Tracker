<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The managed list of venues. Admins can add a venue the moment it's needed
 * (straight from the event form), so new rooms never wait on a developer.
 */
class VenueController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'venues' => Venue::with('building:id,name,color')->orderBy('name')->get(['id', 'name', 'building_id']),
            'buildings' => Building::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:venues,name'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
        ], [
            'name.unique' => 'That venue is already in the list.',
        ]);

        $venue = Venue::create($data)->load('building:id,name,color');

        return response()->json(['venue' => $venue], 201);
    }
}
