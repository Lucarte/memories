<?php

namespace App\Http\Controllers;

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
                // Validation rules
                $validator = Validator::make($request->all(), [
                    'category' => 'nullable|string|in:image,video,audio',
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'kid' => 'required|string|min:5|max:9',
                    'file_path' => 'nullable',
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Check if a memory with the same title exists
                $existingMemory = Memory::where('title', $request->input('title'))
                    ->where('user_id', Auth::id())
                    ->first();

                if ($existingMemory) {
                    return response()->json(['message' => 'A memory with the same title already exists.'], Response::HTTP_CONFLICT);
                }

                // Create a new memory instance
                $memory = new Memory();
                $memory->user_id = Auth::id();
                $memory->category = $request->input('category');
                $memory->title = $request->input('title');
                $memory->description = $request->input('description');
                $memory->kid = $request->input('kid');
                $memory->save();

                // Handle file upload and storage
                if ($request->hasFile('file')) {
                    $extension = $request->file('file')->getClientOriginalExtension();
                    $title = $memory->title;

                    // Specify the disk as 'public'
                    $path = $request->file('file_path')->storeAs('', time() . '_' . $title . '.' . $extension, 'public');

                    // Save file info to the Files table
                    $file = new File();
                    $file->user_id = Auth::id();
                    $file->memory_id = $memory->id;
                    $file->category = $memory->category;
                    $file->save();

                    // Store the file path based on the category
                    switch ($memory->category) {
                        case 'image':
                            $file->image_path = $path;
                            break;
                        case 'video':
                            $file->video_path = $path;
                            break;
                        case 'audio':
                            $file->audio_path = $path;
                            break;
                    }
                } else {
                    info('No file provided');  // Add this debug statement
                }

                return response()->json(['message' => $policyResp->message()], Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(string $title)
    {
        try {
            $memory = Memory::where('title', $title)->first();
            $policyResp = Gate::inspect('delete', $memory);

            if ($policyResp->allowed()) {
                if ($memory) {
                    $memory->delete();
                    return response()->json(['message' => 'Memory deleted successfully!'], Response::HTTP_OK);
                } else {
                    return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
                }
            }
            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL===' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
