<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keywords = explode(" ", $request->text);

        $assets = Asset::where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
                $query->orWhere('name', 'like', "%{$keyword}%");
                $query->orWhere('about', 'like', "%{$keyword}%");
                $query->orWhere('state', 'like', "%{$keyword}%");
                $query->orWhere('location', 'like', "%{$keyword}%");
                $query->orWhere('price', 'like', "% {$keyword} %");
                $query->orWhere('metadata', 'like', "%{$keyword}%");
            }
        })->get();

        if (!$assets) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong'
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Result',
                'data' => $assets
            ],
            200
        );
    }
}
