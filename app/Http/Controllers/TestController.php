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
}
