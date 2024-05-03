<?php

namespace App\Http\Controllers;

use App\Models\Fan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\{Auth, Hash};

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
                'unique:fans'
            ],
            'password' => [
                'required', 'string',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols()
            ],
            // 'passwordConfirmation' => [
            //     'required',
            //     'min:8',
            //     'same:password'
            // ],
            'relationshipToKid' => ['string', Rule::in([
                'Family',
                'Friend',
                'Teacher'
            ])],
            'terms' => ['required']
        ]);

        $fan = Fan::create([
            'first_name' => $request->get('firstName'),
            'last_name' => $request->get('lastName'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'relationship_to_kid' => $request->get('relationshipToKid'),
            'terms' => $request->get('terms'),
        ]);

        Auth::login($fan);
        $request->session()->regenerate();

        $firstName = $fan->first_name;
        return response()->json(['message' => "Registration successful! You can now login, $firstName!"], Response::HTTP_CREATED);
    }
}
