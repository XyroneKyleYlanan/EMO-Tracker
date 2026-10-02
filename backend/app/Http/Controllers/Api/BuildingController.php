<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Buildings set the row colors on the Schedule. Deleting one keeps its venues;
 * they just have no building (gray) until moved.
 */
class BuildingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $building = Building::create($this->validated($request));

        return response()->json(['building' => $building], 201);
    }

    public function update(Request $request, Building $building): JsonResponse
    {
        $building->update($this->validated($request, $building));

        return response()->json(['building' => $building]);
    }

    public function destroy(Building $building): JsonResponse
    {
        $building->delete();

        return response()->json(['message' => 'Building removed. Its venues now have no building.']);
    }

    private function validated(Request $request, ?Building $building = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('buildings', 'name')->ignore($building?->id)],
            'color' => ['required', Rule::in(Building::PALETTE)],
        ], [
            'name.unique' => 'There is already a building with that name.',
            'color.in' => 'Pick one of the colors shown.',
        ]);
    }
}
