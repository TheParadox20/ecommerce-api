<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__.'/auth.php';

// One-time web route for running SEO migrations on cPanel without terminal access (Option B)
// Delete or comment out this route after running it once in your browser.
Route::get('/run-seo-migration', function (\Illuminate\Http\Request $request) {
    $secret = env('MIGRATION_SECRET', 'ngwindsongk_seo_migrate_2026');
    if ($request->query('token') !== $secret) {
        return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid token.'], 403);
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'success' => true,
            'message' => 'Migration ran successfully.',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ], 500);
    }
});
