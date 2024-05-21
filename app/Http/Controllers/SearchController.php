<?php

namespace App\Http\Controllers;

use App\Models\Memory;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    public function CategoryKeywordIndex($category, $keyword)
    {
        $memories = Memory::whereHas('categories', function ($query) use ($category, $keyword) {
            $query->where('category', $category)
                ->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%');
                });
        })->get();

        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found for the specified category and keyword'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['results' => $memories], Response::HTTP_OK);
    }

    public function CategoryOnlyIndex($category)
    {
        $memories = Memory::whereHas('categories', function ($query) use ($category) {
            $query->where('category', $category);
        })->get();

        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found for the specified category'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['results' => $memories], Response::HTTP_OK);
    }

    public function TitleIndex($title)
    {
        $memories = Memory::where('title', 'like', '%' . $title . '%')->get();

        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found with the specified title'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['results' => $memories], Response::HTTP_OK);
    }


    public function DateIndex($date)
    {
        $year = null;
        $month = null;

        // Parse the date parameter
        if (preg_match('/^\d{4}$/', $date)) {
            $year = $date;
        } elseif (preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $date)) {
            $month = ucfirst(strtolower($date));
        } elseif (preg_match('/^(\d{4})_(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $date, $matches)) {
            $year = $matches[1];
            $month = ucfirst(strtolower($matches[2]));
        }

        $memories = Memory::where(function ($query) use ($year, $month) {
            if ($year && $month) {
                $query->where('year', $year)->where('month', $month);
            } elseif ($year) {
                $query->where('year', $year);
            } elseif ($month) {
                $query->where('month', $month);
            }
        })->get();

        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found for the specified date'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['results' => $memories], Response::HTTP_OK);
    }
}
