<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Url;
use App\Models\File;
use App\Models\Memory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                'image_paths.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:10240',
                'audio_paths' => 'nullable|array|max:10',
                'audio_paths.*' => 'nullable|file|mimes:aiff,mpeg,m4a,mp3|max:30720',
                'video_paths' => 'nullable|array|max:10',
                'video_paths.*' => 'nullable|file|mimes:mp4,avi,quicktime,mpeg,mov|max:209715200',
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
    
            // Convert month name to a numeric value
            $monthNames = [
                'January' => 1,
                'February' => 2,
                'March' => 3,
                'April' => 4,
                'May' => 5,
                'June' => 6,
                'July' => 7,
                'August' => 8,
                'September' => 9,
                'October' => 10,
                'November' => 11,
                'December' => 12,
            ];
    
            // Retrieve and convert month name to numeric value
            $year = $request->input('year');
            $monthName = $request->input('month');
            $day = $request->input('day');
            $numericMonth = $monthNames[$monthName] ?? null;
    
            if ($numericMonth) {
                // Create a date string with the numeric month value
                $memory_date = Carbon::createFromDate($year, $numericMonth, $day)->toDateString();
                $memory->memory_date = $memory_date;
            } else {
                // Handle invalid month name if needed
                return response()->json(['error' => 'Invalid month name'], 400);
            }
    
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
                        $path = $uploadedFile->storeAs('uploads', time() . '_' . $title . '.' . $extension, 'spaces');
    
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
            return response()->json(['message' => 'Memory created successfully!'], Response::HTTP_CREATED);
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
        // Fetch all memories with related data
        $memories = Memory::with([
            'files:id,memory_id,file_path',
            'urls',
            'user.avatar',
            'categories:id,category' // Include the categories relationship
        ])
            ->select('id', 'user_id', 'title', 'description', 'year', 'month', 'day', "memory_date", 'created_at', 'updated_at')
            ->get();

        // Sort memories by memory_date in descending order
        $memories = $memories->sortByDesc('memory_date')->values();

        // Map through memories to format file paths and categories
        $memories = $memories->map(function ($memory) {
            $memory->files = $memory->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_path' => $file->file_path,
                ];
            });
            // Format categories to include only relevant details
            $memory->categories = $memory->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->category,
                ];
            });
            return $memory;
        });

        return response()->json([
            'message' => 'LIST OF ALL MEMORIES',
            'Memories' => $memories
        ], Response::HTTP_OK);
    } catch (\Exception $e) {
        return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function index($kid = null)
    {
        return $this->getMemories($kid);
    }

    public function getGabriellasMemories()
    {
        return $this->getMemories('gabriella');
    }

    public function getPablosMemories()
    {
        return $this->getMemories('pablo');
    }

    public function getBrunnisMemories()
    {
        return $this->getMemories('both');
    }

    private function getMemories($kid)
    {
        try {
            if ($kid) {
                $memories = Memory::where('kid', $kid)
                    ->with(['files:id,memory_id,file_path', 'urls', 'user.avatar', 'categories:id,category'])
                    ->select('id', 'user_id', 'title', 'description', 'year', 'month', 'day', 'memory_date', 'created_at', 'updated_at')
                    ->orderBy('memory_date', 'desc')  // Order by memory_date in descending order
                    ->get();
    
                $memories = $memories->map(function ($memory) {
                    $memory->files = $memory->files->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'file_path' => $file->file_path,
                        ];
                    });
                    return $memory;
                });
    
                return response()->json([
                    'message' => 'LIST OF MEMORIES FOR ' . ucfirst($kid),
                    'Memories' => $memories
                ], Response::HTTP_OK);
            }
    
            return response()->json(['message' => 'No kid specified.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    // Single retrieval with files and urls
    public function show(string $title)
    {
        try {
            $memory = Memory::with(['files', 'urls', 'user.avatar', 'categories:id,category'])->where('title', $title)->first();

            if ($memory) {
                return response()->json($memory, Response::HTTP_OK);
            }

            return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            // Log the incoming request data
            Log::info('Update Memory Request Data:', $request->all());

            // Find the memory by ID
            $memory = Memory::where('id', $id)->first();

            if (!$memory) {
                return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
            }

            // Authorization check
            $policyResp = Gate::inspect('update', $memory);
            if (!$policyResp->allowed()) {
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }

            // Define validation rules
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'kid' => 'required|string|min:4|max:9',
                'year' => 'required|integer|min:1900|max:2200',
                'month' => 'nullable|string|min:3|max:9',
                'day' => 'nullable|integer|min:1|max:31',
                'image_paths' => 'nullable|array|max:10',
                'image_paths.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg|max:3145728',
                'audio_paths' => 'nullable|array|max:10',
                'audio_paths.*' => 'nullable|file|mimes:aiff,mpeg,m4a,mp3|max:20971520',
                'video_paths' => 'nullable|array|max:10',
                'video_paths.*' => 'nullable|file|mimes:mp4,avi,quicktime,mpeg,mov|max:209715200',
                'urls' => 'nullable|string',
                'urls.*' => 'nullable|url',
                'category_ids' => 'required|array',
                'category_ids.*' => 'exists:categories,id',
            ];

            // Validate the request data
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

            // Ensure category_ids is an array
            $categoryIds = $request->input('category_ids', []);

            // Associate categories with the memory
            $memory->categories()->sync($categoryIds);

            // Return success response
            return response()->json(['message' => 'Memory updated successfully!'], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('===FATAL=== ' . $e->getMessage());
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}