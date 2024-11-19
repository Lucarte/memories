<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Avatar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewUserSignupNotification;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        // Validate the request input
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

        // Create the user with is_approved set to false
        $user = User::create([
            'first_name' => $request->get('firstName'),
            'last_name' => $request->get('lastName'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'relationship_to_kid' => $request->get('relationshipToKid'),
            'terms' => $request->get('terms') ? 1 : 0,
            'is_approved' => false,  // New field to indicate pending approval
        ]);

        // Upload and associate the avatar with the user, if provided
        if ($request->hasFile('avatar_path')) {
            $uploadedAvatar = $request->file('avatar_path');
            $extension = $uploadedAvatar->getClientOriginalExtension();
            $name = $user->first_name;
            $path = $uploadedAvatar->storeAs('avatars', time() . '_' . $name . '-avatar.' . $extension, 'spaces');

            $avatar = new Avatar();
            $avatar->user_id = $user->id;
            $avatar->avatar_path = $path;
            $avatar->save();
        }

        // Find all admin users to notify them of the new registration
        $adminUsers = User::where('is_admin', true)->get();
        Notification::send($adminUsers, new NewUserSignupNotification($user));

        return response()->json(['message' => 'Registration successful! Your account is awaiting approval.'], Response::HTTP_CREATED);
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
            ], Response::HTTP_BAD_REQUEST);
        }

        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if the user is approved
            if (!$user->is_approved) {
                Auth::logout(); // Log the user out
                return response()->json([
                    'message' => 'Your account is pending approval by the admin.'
                ], Response::HTTP_FORBIDDEN);
            }

            // User is approved, login successful
            $firstName = $user->first_name;
            return response()->json([
                'user' => $user,
                'message' => "Login successful, $firstName!"
            ], Response::HTTP_OK);
        } else {
            // Authentication failed
            return response()->json([
                'message' => 'Login failed'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $user = Auth::user();
        $firstName = $user->first_name;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => "You have been logged out successfully, $firstName!"], Response::HTTP_OK);
    }



    // STATUS - with id
    public function loginStatus()
    {
        if (auth()->check()) {
            $user = auth()->user();
    
            return response()->json([
                'loggedIn' => true,
                'userId' => $user->id,
                'isAdmin' => $user->is_admin,
                'firstName' => $user->first_name,
                'isApproved' => $user->is_approved, // Add this line
            ], 200);
        } else {
            return response()->json([
                'loggedIn' => false,
            ], 200);
        }
    }
}
