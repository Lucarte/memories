<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Avatar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'firstName' => [
                'required',
                'string',
                'min:2',
                'max:16',
            ],
            'lastName' => [
                'required',
                'string',
                'min:2',
                'max:16',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users'
            ],
            'password' => [
                'required', 'string',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols()
            ],
            'passwordConfirmation' => ['required', 'min:8', 'same:password'],
            'relationshipToKid' => ['string', Rule::in([
                'Family',
                'Friend',
                'Teacher'
            ])],
            'terms' => ['required'],
            'avatar_path' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,gif,svg',
                'max:2048'
            ],
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $request->get('firstName'),
            'last_name' => $request->get('lastName'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'relationship_to_kid' => $request->get('relationshipToKid'),
            'terms' => $request->get('terms') ? 1 : 0,
        ]);

        // Create avatar file
        if ($request->hasFile('avatar_path')) {
            $uploadedAvatar = $request->file('avatar_path');
            $extension = $uploadedAvatar->getClientOriginalExtension();
            $name = $user->first_name;
            $path = $uploadedAvatar->storeAs('avatars', time() . '_' . $name . '-' . 'avatar' . '.' . $extension, 'spaces');

            // Create new avatar associated with this user
            $avatar = new Avatar();
            $avatar->user_id = $user->id;
            $avatar->avatar_path = $path;
            $avatar->save();
        }


        Auth::login($user);
        $firstName = $user->first_name;
        return response()->json(['message' => "Registration successful! You can now login, $firstName!"], Response::HTTP_CREATED);
    }

    // LOGIN
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data',
                'errors' => $validator->errors(),
            ]);
        }

        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials)) {
            $fan = Auth::user();
            $firstName = $fan->first_name;

            return response()->json(['fan' => $fan, 'message' => "Login successful, $firstName!"], Response::HTTP_OK);
        } else {
            // Authentication failed
            return response()->json(['message' => 'Login failed'], Response::HTTP_UNAUTHORIZED);
        }
    }

    // LOGOUT
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = Auth::user();
        $firstName = $user->first_name;
        return response()->json(['message' => "You have been logged out successfully, $firstName!"], Response::HTTP_OK);
    }

    // STATUS - with id
    public function loginStatus()
    {
        if (auth()->check()) {
            // Fetch user details
            $user = auth()->user();

            return response()->json([
                'loggedIn' => true,
                'userId' => $user->id,
                'isAdmin' => $user->is_admin,
                'firstName' => $user->first_name,
            ], 200);
        } else {
            return response()->json([
                'loggedIn' => false,
            ], 200);
        }
    }
}
