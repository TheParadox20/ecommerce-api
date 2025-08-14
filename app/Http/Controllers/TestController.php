<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Book;
class TestController extends Controller
{
    public function books()
    {
        $books = Book::all();
        return response()->json($books);
    }

    public function testSession(Request $request)
    {
        $request->session()->put('test_key', 'test_value');
        $value = $request->session()->get('test_key');
        
        return response()->json([
            'session_working' => $value === 'test_value',
            'session_value' => $value,
            'session_id' => $request->session()->getId()
        ]);
    }
}
