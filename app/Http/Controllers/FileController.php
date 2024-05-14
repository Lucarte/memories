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

                // Validate the file
                $request->validate([
                    'file_type' => ['required', 'string', Rule::in(['image', 'video', 'audio'])],
                    // 'file_path' => 'required|file|mimes:jpeg,png,gif,svg,webp,avi,mpeg,quicktime,animaflex,aiff,flac,m4a,mp3,mp4,ogg,wma,heic|max:2048',
                ]);

                // Handle file upload and storage
                if ($request->hasFile('file_path')) {
                    $uploadedFile = $request->file('file_path');
                    $extension = $uploadedFile->extension();

                    // Determine the file type based on the extension
                    $fileType = $request->input('file_type');

                    // Ask memories table for title
                    $title = $file->memory->title;

                    // Get new path
                    $newPath = $uploadedFile->storeAs('', time() . '_' . $title . '.' . $extension, 'public');

                    // Delete the old file if it exists
                    if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }

                    $file->file_path = $newPath;
                    $file->file_type = $fileType;
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
