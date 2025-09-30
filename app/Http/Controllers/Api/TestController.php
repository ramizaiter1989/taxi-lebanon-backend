<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function randomNumber()
    {
        $number = rand(1, 100); // Random number between 1 and 100

        return response()->json([
            'random_number' => $number
        ]);
    }
}
