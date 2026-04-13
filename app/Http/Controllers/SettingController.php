<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Exception;

class SettingController extends Controller
{
    /**
     * Get settings by group (for about page etc)
     */
    public function index(Request $request)
    {
        try {
            $group = $request->query('group', 'general');
            $settings = WebsiteSetting::where('group', $group)->get()->pluck('value', 'key');
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update settings (from Admin)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'group' => 'required|string'
            ]);

            foreach ($validated['settings'] as $key => $value) {
                // Check if the value is an uploaded file
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $path = $value->store('settings', 'public');
                    $valueStr = '/storage/' . $path;
                } else {
                    $valueStr = $value;
                }

                WebsiteSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $valueStr, 'group' => $validated['group']]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
