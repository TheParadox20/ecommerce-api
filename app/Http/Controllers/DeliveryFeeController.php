<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class DeliveryFeeController extends Controller
{
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address_components' => 'required|array',
            'address_components.*.long_name' => 'required|string',
            'address_components.*.types' => 'required|array',
        ]);

        // 1. Try to match by sublocality from address_components
        $sublocality = collect($validated['address_components'])
            ->first(fn($c) => array_intersect($c['types'], ['sublocality', 'sublocality_level_1']));

        if ($sublocality) {
            $location = Location::where('name', $sublocality['long_name'])
                ->whereNotNull('delivery_fee')
                ->first();

            if ($location) {
                return response()->json([
                    'delivery_fee' => (float) $location->delivery_fee,
                    'matched_by' => 'sublocality',
                    'matched_location' => $location->name,
                ]);
            }
        }

        // 2. Fallback: find closest location with a delivery fee using coordinates
        $lat = $validated['latitude'];
        $lng = $validated['longitude'];

        $closest = Location::whereNotNull('delivery_fee')
            ->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance', [$lat, $lng, $lat])
            ->orderBy('distance')
            ->first();

        if ($closest) {
            return response()->json([
                'delivery_fee' => (float) $closest->delivery_fee,
                'matched_by' => 'coordinates',
                'matched_location' => $closest->name,
                'distance_km' => round($closest->distance, 2),
            ]);
        }

        return response()->json([
            'delivery_fee' => null,
            'matched_by' => null,
            'matched_location' => null,
            'message' => 'No delivery zone found for this location',
        ], 404);
    }
}
