<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        // Auth::logout(); // did not work!?

        // try this if does not work
        Auth::guard('web')->logout();

        $user = Auth::user();
        $firstName = $user->first_name;

        return response()->json(['message' => "You have been logged out successfully, $firstName!"]);
    }
}
