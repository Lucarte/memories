<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // Route for 'admin' ONLY
    public function index()
    {
        try {
            $policyResp = Gate::inspect('index', User::class);

            if ($policyResp->allowed()) {
                // Retrieve the list of fans
                $users = User::all();

                return response()->json(['message' => $policyResp->message(), 'users' => $users], Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Routes for admin & profile owners
    public function getById(Request $request, int $id)
    {
        try {
            // Find the User using their id
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            // Check if the current user has the necessary permission
            $policyResp = Gate::inspect('getById', $user);

            if ($policyResp->allowed()) {
                return response()->json(['user' => $user], Response::HTTP_OK);
            } else {
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
            }
            $user = User::where('id', $id)->first();

            $policyResp = Gate::inspect('delete', $user);

            if ($policyResp->allowed()) {
                if ($user) {
                    $user->delete();
                    return response()->json(['message' => 'User deleted successfully'], Response::HTTP_OK);
                } else {
                    return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
                }
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $user = User::where('id', $id)->first();

            $policyResp = Gate::inspect('update', $user);

            if ($policyResp->allowed()) {
                $rules = [
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
                    ],
                    'password' => [
                        // 'required', 'string', 'confirmed', // does not work
                        'required', 'string', 'same:password',
                        Password::min(8)->letters()->numbers()->mixedCase()->symbols()
                    ],
                    'passwordConfirmation' => [
                        'required',
                        'string',
                        'min:8',
                    ],
                    'relationshipToKid' => ['string', Rule::in([
                        'Family',
                        'Friend',
                        'Teacher'
                    ])],
                    'terms' => ['required'],
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                $user->first_name = $request->input('firstName');
                $user->last_name = $request->input('lastName');
                $user->email = $request->input('email');
                $user->password = bcrypt($request->input('password'));
                $user->relationship_to_kid = $request->input('relationshipToKid');
                $user->terms = $request->input('terms');

                $user->save();

                return response()->json(['user' => $user], Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
