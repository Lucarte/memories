<?php

namespace App\Http\Controllers;

use App\Models\Memory;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    // Search by category only
    public function searchByCategory($category)
    {
        // Define the filter for the category
        $filter = ['categories.category = "' . $category . '"'];

        // Perform the search on the 'Memory' model, filtering by category
        $memories = Memory::search('', function ($meilisearch, $query, $options) use ($filter) {
            $options['filter'] = $filter;
            return $meilisearch->search($query, $options);
        })->get();

        // Check if any memories were found
        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found for the specified category'], Response::HTTP_NOT_FOUND);
        }

        // Return the found memories
        return response()->json(['results' => $memories], Response::HTTP_OK);
    }


    public function DateIndex($date)
    {
        $filter = [];
        $year = null;
        $month = null;

        if (preg_match('/^\d{4}$/', $date)) {
            $year = $date;
            $filter[] = 'year = ' . $year;
        } elseif (preg_match('/^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $date)) {
            $month = ucfirst(strtolower($date));
            $filter[] = 'month = ' . $month;
        } elseif (preg_match('/^(\d{4})_(january|february|march|april|may|june|july|august|september|october|november|december)$/i', $date, $matches)) {
            $year = $matches[1];
            $month = ucfirst(strtolower($matches[2]));
            $filter[] = 'year = ' . $year;
            $filter[] = 'month = ' . $month;
        }

        $memories = Memory::search('', function ($meilisearch, $query, $options) use ($filter) {
            $options['filter'] = $filter;
            return $meilisearch->search($query, $options);
        })->get();

        if ($memories->isEmpty()) {
            return response()->json(['message' => 'No memories found for the specified date'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['results' => $memories], Response::HTTP_OK);
    }
}
