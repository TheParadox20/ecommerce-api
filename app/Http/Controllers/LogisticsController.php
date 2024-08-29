<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function index(Request $request){
        $data = [];
        return response()->json($data);
    }
}
