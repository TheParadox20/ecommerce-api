<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exports\LocationsExport;
use App\Exports\LocationsTemplateExport;
use App\Imports\LocationsImport;
use Maatwebsite\Excel\Facades\Excel;

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
            'rider_name' => 'nullable|string',
            'sacco_name' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'rider_fee' => 'nullable|numeric|min:0',
            'sacco_fee' => 'nullable|numeric|min:0',
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
            'rider_name' => 'nullable|string',
            'sacco_name' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'rider_fee' => 'nullable|numeric|min:0',
            'sacco_fee' => 'nullable|numeric|min:0',
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
        if ($request->has('rider_fee') && $request->input('rider_fee') === null) {
             $validated['rider_fee'] = null;
        }
        if ($request->has('sacco_fee') && $request->input('sacco_fee') === null) {
             $validated['sacco_fee'] = null;
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

    /**
     * Public endpoint: return all counties with urban centers for checkout zone picker.
     */
    public function counties()
    {
        $kenyaId = Location::where('name', 'Kenya')->value('id');

        $counties = Location::where('parent_id', $kenyaId)
            ->with(['children' => fn($q) => $q->orderBy('name')->select([
                'id', 'name', 'parent_id', 'delivery_fee', 'sacco_rider', 
                'rider_fee', 'rider_name', 'sacco_fee', 'sacco_name'
            ])])
            ->orderBy('name')
            ->get([
                'id', 'name', 'delivery_fee', 'parent_id', 'sacco_rider',
                'rider_fee', 'rider_name', 'sacco_fee', 'sacco_name'
            ]);

        return response()->json([
            'success' => true,
            'data' => $counties,
        ]);
    }

    public function export()
    {
        return Excel::download(new LocationsExport, 'delivery_zones.xlsx');
    }

    public function template()
    {
        return Excel::download(new LocationsTemplateExport, 'delivery_zones_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new LocationsImport, $request->file('file'));
            return response()->json([
                'success' => true,
                'message' => 'Delivery zones imported successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
