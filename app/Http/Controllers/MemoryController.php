<?php

namespace App\Http\Controllers;

use Imagick;
use FFMpeg\FFMpeg;
use App\Models\Url;
use App\Models\File;
use App\Models\Memory;
use App\Models\Category;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Http\Request;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

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
                'image_paths.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,svg,heic|max:3145728',
                'audio_paths' => 'nullable|array|max:10',
                'audio_paths.*' => 'nullable|file|mimes:aiff,mpeg,m4a,mp3|max:20971520',
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
            $memory->save();
    
            // Handle categories
            $memory->categories()->sync($request->input('category_ids'));
    
            // Handle multiple file uploads
            $filePaths = ['image_paths', 'audio_paths', 'video_paths'];
    
            foreach ($filePaths as $fileType) {
                if ($request->hasFile($fileType) && is_array($request->file($fileType))) {
                    foreach ($request->file($fileType) as $uploadedFile) {
                        $extension = $uploadedFile->getClientOriginalExtension();
                        $title = $memory->title;
                        $originalPath = 'uploads/' . time() . '_' . $title . '.' . $extension;

                        // Upload the file directly to Spaces
                        Storage::disk('spaces')->put($originalPath, fopen($uploadedFile->getPathname(), 'r+'), 'public');
    
                        // Create a new file associated with this memory
                        $file = new File();
                        $file->user_id = Auth::id();
                        $file->memory_id = $memory->id;
                        $file->file_path = $originalPath;
                        $file->save();
                        
                        // Transcode videos if applicable
                        if (in_array($extension, ['mp4', 'avi', 'quicktime', 'mpeg', 'mov'])) {
                            $newFilePath = $this->transcodeVideo($originalPath, $memory->id);
                            if ($newFilePath) {
                                $file->file_path = $newFilePath; // Update file path with transcoded version
                                $file->save(); // Save the updated file record
                            }
                        }
                        
                        // Convert audio formats
                        if (in_array($extension, ['aiff', 'mpeg', 'm4a', 'mp3'])) {
                            $newFilePath = $this->transcodeAudio(storage_path('app/' . $originalPath), $memory->id);
                            $file->file_path = $newFilePath; // Update file path with transcoded version
                            $file->save(); // Save the updated file record
                        }
    
                        // Convert HEIC to JPEG
                        if ($extension === 'heic') {
                            $newFilePath = $this->convertHeicToJpeg(storage_path('app/' . $originalPath), $memory->id);
                            $file->file_path = $newFilePath; // Update file path with converted image
                            $file->save(); // Save the updated file record
                        }
                        
                        unlink(storage_path('app/' . $originalPath));
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
    
    private function transcodeVideo($filePath, $memoryId)
    {
        try {
            $ffmpeg = FFMpeg::create();
            
            // Construct the full URL for accessing the video in DigitalOcean Spaces
            $spacesUrl = env('DO_SPACES_BUCKET') . '.' . env('DO_SPACES_DEFAULT_REGION') . '.cdn.digitaloceanspaces.com/' . $filePath;
    
            // Open the video from the DigitalOcean Spaces URL
            $video = $ffmpeg->open($spacesUrl);
    
            $outputFormat = new X264();
            $outputFormat->setKiloBitrate(1000)->setAudioKiloBitrate(128);
    
            // Define a temporary local path to save the converted video
            $tempPath = storage_path('app/converted-video-' . $memoryId . '.mp4');
            $video->save($outputFormat, $tempPath);
    
            // Upload the transcoded video back to DigitalOcean Spaces
            $outputPath = 'uploads/converted-video-' . $memoryId . '.mp4';
            $uploaded = Storage::disk('spaces')->put($outputPath, fopen($tempPath, 'r+'), 'public');
    
            // Delete the temporary file from local storage
            unlink($tempPath);
    
            if ($uploaded) {
                return $outputPath; // Return the path of the uploaded video
            } else {
                Log::error('Failed to upload transcoded video to DigitalOcean Spaces.');
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error during video transcoding: ' . $e->getMessage());
            return null;
        }
    }
    
    
    
    private function transcodeAudio($path, $memoryId)
    {
        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($path);
    
        // Define a temporary local path to save the converted audio file
        $tempPath = 'converted-audio-' . $memoryId . '.mp3';
        $audio->save(new Mp3(), storage_path('app/' . $tempPath));
    
        // Upload to DigitalOcean Spaces
        $outputPath = 'uploads/converted-audio-' . $memoryId . '.mp3';
        $uploaded = Storage::disk('spaces')->put($outputPath, fopen(storage_path('app/' . $tempPath), 'r+'), 'public');
    
        // Delete the local temporary file
        unlink(storage_path('app/' . $tempPath));
    
        if ($uploaded) {
            // Save the DigitalOcean Spaces file path in the database
            $file = new File();
            $file->user_id = Auth::id();
            $file->memory_id = $memoryId;
            $file->file_path = $outputPath;
            $file->save();
        } else {
            Log::error('Failed to upload transcoded audio to DigitalOcean Spaces.');
        }
    }
    

    private function convertHeicToJpeg($path, $memoryId)
    {
        $imagick = new \Imagick($path);
        
        // Define a temporary local path to save the converted JPEG file
        $tempPath = 'converted-image-' . $memoryId . '.jpg';
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage(storage_path('app/' . $tempPath));

        // Upload to DigitalOcean Spaces
        $outputPath = 'uploads/converted-image-' . $memoryId . '.jpg';
        Storage::disk('spaces')->put($outputPath, fopen(storage_path('app/' . $tempPath), 'r+'), 'public');
        
        // Delete the local temporary file
        unlink(storage_path('app/' . $tempPath));

        // Save the DigitalOcean Spaces file path in the database
        $file = new File();
        $file->user_id = Auth::id();
        $file->memory_id = $memoryId;
        $file->file_path = $outputPath;
        $file->save();
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
            $memories = Memory::with([
                'files:id,memory_id,file_path',
                'urls',
                'user.avatar',
                'categories:id,category' // Include the categories relationship
            ])
                ->select('id', 'user_id', 'title', 'description', 'year', 'month', 'day', 'created_at', 'updated_at')
                ->get();

            // Map through memories and ensure the file paths and categories are set correctly
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
                        'name' => $category->name,
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
                    ->select('id', 'user_id', 'title', 'description', 'year', 'month', 'day', 'created_at', 'updated_at')
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
}
