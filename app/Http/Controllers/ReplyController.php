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
    public function create(Request $request, $id)
    {
        try {
            $policyResp = Gate::inspect('create', Reply::class);

            if ($policyResp->allowed()) {
                $comment = Comment::where('id', $id)->first();

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

    public function delete(Request $request, $id)
    {
        try {
            $reply = Reply::findOrFail($id);

            $policyResp = Gate::inspect('delete', $reply);

            if ($policyResp->allowed()) {
                $reply->delete();

                return response()->json(['message' => 'Reply deleted successfully'], Response::HTTP_OK);
            } else {
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $commentId, $replyId)
    {
        try {
            // Find the comment first
            $comment = Comment::findOrFail($commentId);

            // Find the reply within the comment
            $reply = $comment->replies()->findOrFail($replyId);
            // $reply = Reply::findOrFail($id);

            $policyResp = Gate::inspect('update', $reply);

            if ($policyResp->allowed()) {
                // Validate the request data
                $validator = Validator::make($request->all(), [
                    'reply' => 'required|string',
                ]);

                if ($validator->fails()) {
                    return response()->json(['message' => $validator->errors()], Response::HTTP_BAD_REQUEST);
                }

                // Update the reply
                $reply->reply = $request->input('reply');
                $reply->save();

                return response()->json(['message' => 'Reply updated successfully'], Response::HTTP_OK);
            } else {
                return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
