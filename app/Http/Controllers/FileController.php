<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    public function show(string $title)
    {
        try {
            $file = File::find($title);

            if (!$file) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Construct the full path to the file in the storage directory
            $filePath = storage_path('app/public/' . $file->file_path);

            // Check if the file exists
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'File not found'], Response::HTTP_NOT_FOUND);
            }

            // Read the file and return it
            $fileData = file_get_contents($filePath);

            // Determine the content type based on the image file extension
            $contentType = mime_content_type($filePath);

            return response($fileData)->header('Content-Type', $contentType);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function delete(string $title)
    {
        try {
            $file = File::find($title);

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
    public function update(string $title)
    {
    }
}
