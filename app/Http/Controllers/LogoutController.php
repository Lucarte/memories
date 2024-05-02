<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::logout();

        // // try this if does not work
        // Auth::guard('web')->logout();

        return response()->json(['message' => 'You have been logged out successfully!']);
    }
}
