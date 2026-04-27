<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    /**
     * Display a listing of locations (Delivery Zones).
     */
    public function index(Request $request)
    {
        // Return both counties and zones so the UI can populate dropdowns and lists
        $query = Location::with('parent');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $locations = $query->orderBy('name', 'asc')->get();

        return response()->json($locations);
    }

    /**
     * Store a newly created location (Delivery Zone).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'required|exists:locations,id',
            'sacco_rider' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $validated['short_name'] = $validated['name']; // Fallback for short_name

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery zone created successfully.',
            'location' => $location
        ], 201);
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'parent_id' => 'sometimes|required|exists:locations,id',
            'sacco_rider' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if (isset($validated['name'])) {
            $validated['short_name'] = $validated['name'];
        }

        // If delivery_fee is passed as an empty string, set it to null
        if ($request->has('delivery_fee') && $request->input('delivery_fee') === null) {
             $validated['delivery_fee'] = null;
        }

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery zone updated successfully.',
            'location' => $location
        ]);
    }

    /**
     * Remove the specified location.
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery zone deleted successfully.'
        ]);
    }
}
