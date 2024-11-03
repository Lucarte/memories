<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{

    public function show(int $id)
    {
        try {
            // Find the file by its ID
            $file = File::find($id);

            if (!$file) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Construct full path to file in the storage directory
            $filePath = storage_path('app/public/' . $file->file_path);

            // Check if file exists
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Read content and return it
            $fileData = file_get_contents($filePath);

            // Determine content type based on file extension
            $contentType = mime_content_type($filePath);

            return response($fileData)->header('Content-Type', $contentType);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id)
    {
        try {
            // Find the file by its ID
            $file = File::find($id);

            // Check authorization using Gate policy
            $policyResp = Gate::inspect('delete', $file);

            if ($policyResp->allowed()) {
                if ($file) {
                    $file->delete();
                    return response()->json(['message' => 'File deleted successfully'], Response::HTTP_OK);
                } else {
                    return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
                }
            }
            return response()->json(['message' => 'FilePolicy - delete - denied'], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Check policy for authorization
            $policyResp = Gate::inspect('update', $file);

            if ($policyResp->allowed()) {

                // Validate the file if included in the request
                if ($request->hasFile('file_path')) {
                    $request->validate([
                        'file_type' => 'nullable|string|in:image,video,audio',
                        'file_path' => [
                            'required',
                            'file',
                            function ($attribute, $value, $fail) use ($request) {
                                $maxSize = 0;
                                $fileType = $value->getMimeType();

                                switch ($fileType) {
                                    case 'image/jpeg':
                                    case 'image/png':
                                    case 'image/gif':
                                    case 'image/svg+xml':
                                        $maxSize = 10 * 1024 * 1024; // 10MB
                                        break;
                                    case 'audio/x-aiff':
                                    case 'audio/mpeg':
                                    case 'audio/mp3':
                                        $maxSize = 30 * 1024 * 1024; // 30MB
                                        break;
                                    case 'video/mp4':
                                    case 'video/avi':
                                    case 'video/quicktime':
                                    case 'video/mpeg':
                                        $maxSize = 100 * 1024 * 1024; // 100MB
                                        break;
                                    default:
                                        $fail("Unsupported file type: {$fileType}");
                                        return;
                                }

                                if ($value->getSize() > $maxSize) {
                                    $fail("The {$attribute} must not be greater than {$maxSize} bytes.");
                                }
                            },
                        ],
                    ]);

                    $uploadedFile = $request->file('file_path');
                    $extension = $uploadedFile->getClientOriginalExtension();

                    // Get new path
                    $newPath = $uploadedFile->storeAs('uploads', time() . '_' . $file->memory->title . '.' . $extension, 'public');

                    // Delete the old file if it exists
                    if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }

                    $file->file_path = $newPath;
                } else {
                    // Validate only the file type if it's being updated
                    $request->validate([
                        'file_type' => 'nullable|string|in:image,video,audio',
                    ]);
                }



                $file->save();
                return response()->json(['message' => 'File updated successfully'], Response::HTTP_OK);
            }

            return response()->json(['message' => 'FilePolicy - update - denied'], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
