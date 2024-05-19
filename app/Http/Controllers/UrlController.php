<?php

namespace App\Http\Controllers;

use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UrlController extends Controller
{
    public function show(int $id)
    {
        try {
            $url = Url::find($id);

            if (!$url) {
                return response()->json(['message' => 'URL not found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['url' => $url], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id)
    {
        try {
            $url = Url::find($id);

            $policyResp = Gate::inspect('delete', $url);

            if ($policyResp->allowed()) {
                if ($url) {
                    $url->delete();
                    return response()->json(['message' => 'URL deleted successfully'], Response::HTTP_OK);
                } else {
                    return response()->json(['message' => 'URL not found'], Response::HTTP_NOT_FOUND);
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
            $url = Url::find($id);

            if (!$url) {
                return response()->json(['message' => 'URL not found'], Response::HTTP_NOT_FOUND);
            }

            $policyResp = Gate::inspect('update', $url);

            if ($policyResp->allowed()) {
                $request->validate([
                    'url_address' => 'required|url',
                ]);

                $url->url_address = $request->input('url_address');
                $url->save();

                return response()->json(['message' => 'URL updated successfully'], Response::HTTP_OK);
            }

            return response()->json(['message' => $policyResp->message()], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json(['message' => '===FATAL=== ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
