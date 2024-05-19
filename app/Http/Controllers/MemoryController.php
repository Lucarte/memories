<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\File;
use App\Models\Memory;
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
    public function createWithFile(Request $request)
    {
        try {
            $policyResp = Gate::inspect('createWithFile', Memory::class);

            if ($policyResp->allowed()) {
                // Validation rules for memory creation
                $rules = [
                    'title' => 'required|string|max:255',
                    'description' => 'required|string|max:2000',
                    'kid' => 'required|string|min:4|max:9',
                    'year' => 'required|integer|min:1900|max:2200',
                    'month' => 'nullable|string|min:3|max:9',
                    'day' => 'nullable|integer|min:1|max:31',
                    'file_paths' => 'nullable|array|max:10',
                    'file_paths.*' => [
                        'nullable',
                        'file',
                        function ($attribute, $value, $fail) {
                            $maxSize = 0;
                            $fileType = $value->getMimeType();

                            switch ($fileType) {
                                case 'image/jpeg':
                                case 'image/png':
                                case 'image/gif':
                                case 'image/svg':
                                    $maxSize = 2024 * 1024; // 1MB
                                    break;
                                case 'audio/x-aiff':
                                case 'audio/mpeg':
                                case 'audio/mp3':
                                    $maxSize = 20240 * 1024; // 10MB
                                    // Inside the closure for file size validation
                                    Log::channel('stack')->info('Uploaded File:', ['name' => $value->getClientOriginalName(), 'size' => $value->getSize(), 'type' => $fileType]);
                                    break;
                                case 'video/mp4':
                                case 'video/avi':
                                case 'video/quicktime':
                                case 'video/mpeg':
                                    $maxSize = 202400 * 1024; // 100MB
                                    break;
                            }
                            if ($value->getSize() > $maxSize) {
                                $fail("The {$attribute} must not be greater than {$maxSize} bytes.");
                            }
                        },

                    ],
                    'file_types' => 'nullable|array',
                    'file_types.*' => ['nullable', 'string', Rule::in(['image', 'video', 'audio', 'url'])],
                    'urls' => 'nullable|array',
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

                // Handle multiple file uploads with associated file types
                if ($request->hasFile('file_paths') && is_array($request->file('file_paths'))) {
                    $filePaths = $request->file('file_paths');
                    $fileTypes = $request->input('file_types', []);

                    foreach ($filePaths as $index => $uploadedFile) {
                        $extension = $uploadedFile->getClientOriginalExtension();
                        $title = $memory->title;
                        $path = $uploadedFile->storeAs('uploads', time() . '_' . $title . '.' . $extension, 'public');

                        // Create a new file associated with this memory
                        $file = new File();
                        $file->user_id = Auth::id();
                        $file->memory_id = $memory->id;
                        $file->file_path = $path;
                        $file->file_type = $fileTypes[$index] ?? null; // Ensure file_type is matched with the file
                        $file->save();
                    }
                }

                // Handle URLs
                if ($request->filled('urls')) {
                    foreach ($request->input('urls') as $urlAddress) {
                        $url = new Url();
                        $url->url_address = $urlAddress;
                        $url->memory_id = $memory->id;
                        $url->save();
                    }
                }

                // Return success response
                return response()->json(['message' => 'Memory created successfully with associated files/urls'], Response::HTTP_CREATED);
            } else {
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
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
