<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function index()
    {
        $drafts = Draft::latest()->get();
        return response()->json($drafts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'data' => 'required|array',
        ]);

        $draft = Draft::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully',
            'draft' => $draft,
        ], 201);
    }

    public function show(string $id)
    {
        $draft = Draft::findOrFail($id);
        return response()->json($draft);
    }

    public function update(Request $request, string $id)
    {
        $draft = Draft::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'data' => 'required|array',
        ]);

        $draft->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Draft updated successfully',
            'draft' => $draft,
        ]);
    }

    public function destroy(string $id)
    {
        $draft = Draft::findOrFail($id);
        $draft->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully',
        ]);
    }
}
