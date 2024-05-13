<?php

namespace App\Http\Controllers;

use App\Models\{File, Memory};
use Illuminate\Http\{Request, Response};
use Illuminate\Support\Facades\{Auth, Gate, Storage, Validator};

class FileController extends Controller
{

    public function show(int $id)
    {
        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Get the associated memory of the file to find the file_type
            $memory = Memory::find($file->memory_id);

            if (!$memory) {
                return response()->json(['message' => 'File does not have an associated memory'], Response::HTTP_BAD_REQUEST);
            }

            // Get file_type
            $fileType = $memory->file_type;
            $filePath = $memory->file_path;

            // if ($fileType === 'image') {
            //     $filePath = storage_path('app/public/' . $file->image_path);
            // } elseif ($fileType === 'video') {
            //     $filePath = storage_path('app/public/' . $file->video_path);
            // } elseif ($fileType === 'audio') {
            //     $filePath = storage_path('app/public/' . $file->audio_path);
            // } else {
            //     // If another file type
            //     return response()->json(['message' => 'Invalid file type'], Response::HTTP_BAD_REQUEST);
            // }

            // Check if the file exists
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Read the file and return it
            $fileData = file_get_contents($filePath);

            // Determine the content type based on the file extension
            $contentType = mime_content_type($filePath);

            return response($fileData)->header('Content-Type', $contentType);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id)
    {
        try {
            $file = File::find($id);

            if (!$file) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Check authorization using Gate policy
            $policyResp = Gate::inspect('delete', $file);

            if ($policyResp->allowed()) {
                if ($file) {
                    // Delete file
                    $file->delete();
                    return response()->json(['message' => 'File deleted successfully'], Response::HTTP_OK);
                }
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
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
                // Handle file upload and storage
                if ($request->hasFile('file_path')) {
                    // Determine which specific path to update based on the file type
                    $pathField = $this->getPathFieldForFileType($file->file_type);

                    // Delete the old file if it exists
                    if ($file->$pathField) {
                        Storage::delete($file->$pathField);
                    }

                    // Store the new file
                    $newFilePath = $request->file('file_path')->store('uploads', 'public');
                    $file->$pathField = $newFilePath;
                }

                $file->save();

                return response()->json(['message' => 'File updated successfully'], Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getPathFieldForFileType($fileType)
    {
        switch ($fileType) {
            case 'image':
                return 'image_path';
            case 'video':
                return 'video_path';
            case 'audio':
                return 'audio_path';
            default:
                return null; // Handle invalid file types
        }
    }
}
