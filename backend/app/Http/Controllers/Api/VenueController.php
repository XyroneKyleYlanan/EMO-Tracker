<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The managed list of venues. Admins can add a venue the moment it's needed
 * (straight from the event form), so new rooms never wait on a developer.
 */
class VenueController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'venues' => Venue::with('building:id,name,color')->withCount('events')->orderBy('name')->get(['id', 'name', 'building_id']),
            'buildings' => Building::withCount('venues')->orderBy('name')->get(['id', 'name', 'color']),
            'palette' => Building::PALETTE,
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

    public function update(Request $request, Venue $venue): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('venues', 'name')->ignore($venue->id)],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
        ], [
            'name.unique' => 'That venue is already in the list. Merge them instead.',
        ]);

        $venue->update($data);

        return response()->json(['venue' => $venue->load('building:id,name,color')]);
    }

    /**
     * Only unused venues can be deleted: removing one that events use would
     * erase where those events are held. Merge it into another venue instead.
     */
    public function destroy(Venue $venue): JsonResponse
    {
        if ($venue->events()->exists()) {
            return response()->json([
                'message' => 'This venue is used by events. Merge it into another venue instead.',
            ], 422);
        }

        $venue->delete();

        return response()->json(['message' => 'Venue deleted.']);
    }

    /**
     * Fix duplicates such as "UHALL" and "University Hall": move every event to
     * the other venue, then remove this one.
     */
    public function merge(Request $request, Venue $venue): JsonResponse
    {
        $data = $request->validate([
            'into_id' => ['required', 'integer', 'exists:venues,id', Rule::notIn([$venue->id])],
        ], [
            'into_id.not_in' => 'Choose a different venue to merge into.',
        ]);

        $moved = DB::transaction(function () use ($venue, $data) {
            $moved = Event::where('venue_id', $venue->id)->update(['venue_id' => $data['into_id']]);
            $venue->delete();

            return $moved;
        });

        return response()->json(['message' => "Merged. {$moved} event(s) moved.", 'moved' => $moved]);
    }
}
