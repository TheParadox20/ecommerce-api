<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        return Attribute::with('attributeValues')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:attributes,name',
        ]);
        $attribute = Attribute::create($validated);
        return response()->json($attribute->load('attributeValues'), 201);
    }

    public function show($id)
    {
        $attribute = Attribute::with('attributeValues')->findOrFail($id);
        return response()->json($attribute);
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:attributes,name,' . $id,
        ]);
        $attribute->update($validated);
        return response()->json($attribute->load('attributeValues'));
    }

    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted']);
    }
}
