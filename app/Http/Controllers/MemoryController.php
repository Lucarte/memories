<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\File;
use App\Models\Memory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
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

    public function getCategories()
    {
        try {
            $categories = Category::all();
            return response()->json($categories, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching categories: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createWithFile(Request $request)
    {
        try {
            // Validation rules for memory creation
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'kid' => 'required|string|min:4|max:9',
                'year' => 'required|integer|min:1900|max:2200',
                'month' => 'nullable|string|min:3|max:9',
                'day' => 'nullable|integer|min:1|max:31',
                'image_paths' => 'nullable|array|max:10',
                'image_paths.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:3145728', // Example for images
                'audio_paths' => 'nullable|array|max:10',
                'audio_paths.*' => 'nullable|file|mimes:aiff,mpeg,m4a,mp3|max:20971520', // Example for audio
                'video_paths' => 'nullable|array|max:10',
                'video_paths.*' => 'nullable|file|mimes:mp4,avi,quicktime,mpeg,mov|max:209715200', // Example for video
                'urls' => 'nullable|string',
                'urls.*' => 'nullable|url',
                'category_ids' => 'required|array',
                'category_ids.*' => 'exists:categories,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }

            // Create a new memory instance
            $memory = new Memory();
            $memory->user_id = Auth::id();
            $memory->title = $request->input('title');
            $memory->description = $request->input('description');
            $memory->kid = $request->input('kid');
            $memory->year = $request->input('year');
            $memory->month = $request->input('month');
            $memory->day = $request->input('day');
            $memory->save();

            // Handle categories
            $memory->categories()->sync($request->input('category_ids'));

            // Handle multiple file uploads
            $filePaths = ['image_paths', 'audio_paths', 'video_paths'];

            foreach ($filePaths as $fileType) {
                if ($request->hasFile($fileType) && is_array($request->file($fileType))) {
                    foreach ($request->file($fileType) as $index => $uploadedFile) {
                        $extension = $uploadedFile->getClientOriginalExtension();
                        $title = $memory->title;
                        $path = $uploadedFile->storeAs('uploads', time() . '_' . $title . '.' . $extension, 'public');

                        // Create a new file associated with this memory
                        $file = new File();
                        $file->user_id = Auth::id();
                        $file->memory_id = $memory->id;
                        $file->file_path = $path;
                        $file->save();
                    }
                }
            }

            // Handle URLs
            if ($request->filled('urls')) {
                // Split the comma-separated URLs into an array
                $urlAddresses = explode(',', $request->input('urls'));
                foreach ($urlAddresses as $urlAddress) {
                    $url = new Url();
                    $url->url_address = trim($urlAddress);
                    $url->memory_id = $memory->id;
                    $url->save();
                }
            }

            // Return success response
            return response()->json(['message' => 'Memory created successfully with associated files/urls'], Response::HTTP_CREATED);
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

    public function getAllMemories()
    {
        try {
            $memories = Memory::with(['files', 'urls'])->get();

            foreach ($memories as $memory) {
                foreach ($memory->files as $file) {
                    // Get file data and add it to the file object
                    $filePath = storage_path('app/public/' . $file->file_path);

                    if (file_exists($filePath)) {
                        $file->file_data = base64_encode(file_get_contents($filePath));
                    } else {
                        $file->file_data = null; // Handle file not found scenario
                    }
                }
            }

            return response()->json([
                'message' => 'LIST OF ALL MEMORIES',
                'Memories' => $memories
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Retrieval of memmories acc. to kid
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

    // Single retrieval with files and urls
    public function show(string $title)
    {
        try {
            // Retrieve the memory along with its files and URLs
            $memory = Memory::with(['files', 'urls'])->where('title', $title)->first();

            if ($memory) {
                return response()->json($memory, Response::HTTP_OK);
            }

            // Memory not found, return an error response
            return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                    'description' => 'required|string|max:2000',
                    'kid' => 'required|string|min:4|max:9',
                    'year' => 'required|integer|min:1900|max:2200',
                    'month' => 'nullable|string|min:1|max:12',
                    'day' => 'nullable|integer|min:1|max:31',
                    'category_ids' => 'required',
                    'category_ids.*' => 'exists:categories,id',
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Update the memory instance
                $memory->title = $request->input('title');
                $memory->description = $request->input('description');
                $memory->kid = $request->input('kid');
                $memory->year = $request->input('year');
                $memory->month = $request->input('month');
                $memory->day = $request->input('day');
                $memory->save();

                // Associate categories with the memory
                $categoryIds = explode(',', $request->input('category_ids'));
                $memory->categories()->sync($categoryIds);

                return response()->json((['message' => 'Memory updated successfully!']), Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
