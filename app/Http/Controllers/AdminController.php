<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::where('is_approved', false)->get();  // Fetch unapproved users
        return response()->json($users);
    }

    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->is_approved = true;
        $user->save();

        return response()->json(['message' => 'User approved successfully']);
    }
}

