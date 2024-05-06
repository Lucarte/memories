<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\File;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class MemoryController extends Controller
{
    public function create(Request $request)
    {
        try {
            $policyResp = Gate::inspect('create', Memory::class);

            if ($policyResp->allowed()) {
                $rules = [
                    'category' => 'required|string',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'file_path' => 'required|image|mimes:jpg, jpeg, png, bmp, gif, svg, or webp|max:2048|mimetypes:video/avi,video/mpeg,video/quicktime', // New validation rule for the image
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Check if a memory with the same title and author exists
                $existingBook = Memory::where('title', $request->input('title'))
                    ->where('author_id', $request->input('author_id'))
                    ->first();

                if ($existingBook) {
                    return response()->json(['message' => 'A memory with the same title and author already exists.'], Response::HTTP_CONFLICT);
                }

                // Get the authenticated user
                $user = Auth::user();

                // Create a new memory instance and set its attributes
                $memory = new Memory();
                $memory->user_id = $user->id;
                $memory->category = $request->input('category');
                $memory->title = $request->input('title');
                $memory->description = $request->input('description');
                $memory->file = $request->input('file');

                // Save the memory
                $memory->save();

                // Handle image upload and storage
                if ($request->hasFile('file_path')) {
                    $extension = '.' . $request->file('file_path')->extension();
                    $title = $memory->title;

                    // Specify the disk as 'public'
                    $path = $request->file('file_path')->storeAs('', time() . '_' . $title . $extension, 'public');

                    // Save file information to the Covers table
                    $file = new File();
                    $file->user_id = $user->id;
                    $file->book_id = $memory->id;
                    $file->file_path = $path;
                    $file->save();
                }


                return response()->json(['message' => $policyResp->message()], Response::HTTP_CREATED);
            }
        } catch (Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
