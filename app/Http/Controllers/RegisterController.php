<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Avatar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'firstName' => [
                'required',
                'string',
                'min:4',
                'max:16',
            ],
            'lastName' => [
                'required',
                'string',
                'min:4',
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
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $request->get('firstName'),
            'last_name' => $request->get('lastName'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'relationship_to_kid' => $request->get('relationshipToKid'),
            'terms' => $request->get('terms'),
        ]);

        // Create avatar file
        if ($request->hasFile('avatar_path')) {
            $uploadedAvatar = $request->file('avatar_path');
            $extension = $uploadedAvatar->getClientOriginalExtension();
            $name = $user->first_name;
            // $path = $uploadedAvatar->storeAs('', time() . '_' . $name . '-' . 'avatar' . '.' . $extension, 'public/avatars'); // Why does it not work?
            $path = $uploadedAvatar->storeAs('avatars', time() . '_' . $name . '-' . 'avatar' . '.' . $extension, 'public');

            // Create new avatar associated with this user
            $avatar = new Avatar();
            $avatar->user_id = $user->id;
            $avatar->avatar_path = $path;
            $avatar->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $firstName = $user->first_name;
        return response()->json(['message' => "Registration successful! You can now login, $firstName!"], Response::HTTP_CREATED);
    }
}
