<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use App\Models\Memory;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ReplyController extends Controller
{
    public function create(Request $request, $title, $id)
    {
        try {
            $policyResp = Gate::inspect('create', Reply::class);

            if ($policyResp->allowed()) {
                // Retrieve the memory ID based on the title
                $memory = Memory::where('title', $title)->first();
                $comment = Comment::where('id', $id)->first();

                if (!$memory) {
                    return response()->json(['message' => 'Memory not found'], Response::HTTP_NOT_FOUND);
                }

                // Validate
                $rules = [
                    'reply' => 'required|string',
                ];

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Create a new reply instance
                $reply = new Reply();
                $reply->user_id = Auth::id();
                $reply->memory_id = $memory->id; // Assign the memory ID
                $reply->comment_id = $comment->id; // Assign the comment ID
                $reply->reply = $request->input('reply'); // Assign the reply text
                $reply->save();

                // Return success response
                return response()->json(['message' => 'Reply created successfully'], Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Request $request, $title, $id)
    {
        try {
            // Find the comment by its ID
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json(['message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            // Check authorization using Gate policy
            $policyResp = Gate::inspect('delete', $comment);

            if ($policyResp->allowed()) {
                // Delete the comment
                $comment->delete();
                return response()->json(['message' => 'Comment deleted successfully'], Response::HTTP_OK);
            } else {
                // If not authorized, return a 403 Forbidden response with the appropriate message
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $title, $id)
    {
        try {
            // Find the comment by its ID
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json(['message' => 'Comment not found'], Response::HTTP_NOT_FOUND);
            }

            // Check policy for authorization
            $policyResp = Gate::inspect('update', $comment);

            if ($policyResp->allowed()) {
                // Validate the request
                $request->validate([
                    'comment' => 'required|string',
                ]);

                // Update the comment
                $comment->comment = $request->input('comment');
                $comment->save();

                return response()->json(['message' => 'Comment updated successfully'], Response::HTTP_OK);
            } else {
                // If not authorized, return a 403 Forbidden response with the appropriate message
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
