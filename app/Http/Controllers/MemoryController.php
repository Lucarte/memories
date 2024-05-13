<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class MemoryController extends Controller
{
    protected $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function createWithFile(Request $request)
    {
        try {
            $policyResp = Gate::inspect('create', Memory::class);

            if ($policyResp->allowed()) {
                // Validation rules for memory creation
                $rules = [
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'category' => 'required|string',
                    'kid' => 'required|string|min:5|max:9',
                    // 'file_path' => 'file|mimes:jpeg,png,gif,svg,webp,avi,mpeg,quicktime,animaflex,aiff,flac,m4a,mp3,mp4,ogg,wma,heic,aiff|max:2048',
                    'file_type' => ['nullable', 'string', Rule::in(['image', 'video', 'audio'])],
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
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
                if ($request->hasFile('file_path')) {
                    $uploadedFile = $request->file('file_path');
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $title = $memory->title;
                    $path = $uploadedFile->storeAs('', time() . '_' . $title . '.' . $extension, 'public');

                    // Create a new file associated with this memory
                    $file = new File();
                    $file->user_id = Auth::id();
                    $file->memory_id = $memory->id;
                    $file->file_path = $path;
                    $file->file_type = $request->input('file_type');
                    $file->save();
                }

                // Return success response
                return response()->json(['message' => 'Memory created successfully with associated file'], Response::HTTP_CREATED);
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

    public function index($kid = null)
    {
        try {
            if ($kid) {
                // If $kid is provided, filter memories for that kid
                $memories = Memory::where('kid', $kid)->get();
                $message = 'LIST OF MEMORIES FOR ' . $kid;
            } else {
                // If $kid is not provided, get all memories
                $memories = Memory::all();
                $message = 'LIST OF ALL MEMORIES';
            }

            return response()->json(['message' => $message, 'Memories' => $memories], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $title)
    {
        try {
            $memory = Memory::where('title', $title)->first();

            if ($memory) {

                $response = [
                    'memory' => $memory,
                ];

                return response()->json($response, Response::HTTP_OK);
            }

            // Memory not found, return an error response
            return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, string $title)
    {
        try {
            $memory = Memory::where('title', $title)->first();

            $policyResp = Gate::inspect('update', $memory);

            if ($policyResp->allowed()) {
                $rules = [
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'category' => 'required|string',
                    'kid' => 'required|string|min:5|max:9',
                    'file_type' => 'nullable|string|in:image,video,audio',
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Update the memory instance
                $memory->category = $request->input('category');
                $memory->title = $request->input('title');
                $memory->description = $request->input('description');
                $memory->kid = $request->input('kid');
                $memory->file_type = $request->input('file_type');
                $memory->save();

                return response()->json((['message' => 'Memory updated successfully!']), Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
