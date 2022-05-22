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
                $query->orWhere('type', 'like', "%{$keyword}%");
                $query->orWhere('state', 'like', "%{$keyword}%");
                $query->orWhere('location', 'like', "%{$keyword}%");
                $query->orWhere('name', 'like', "%{$keyword}%");
                $query->orWhere('price', 'like', "% {$keyword} %");
                $query->orWhere('metadata', 'like', "%{$keyword}%");
                $query->orWhere('about', 'like', "%{$keyword}%");
            }

            // foreach ($keywords as $keyword) {
            //     $query->selectRaw(function ($querySelect) use ($keyword) {
            //         $querySelect->selectRaw('
            //         SELECT email,
            //         name,
            //         Round ((Char_length(Concat(email, name)) - Char_length(REPLACE ( Concat(email,name), "first_keyword", ""))) / Char_length("first_keyword"))
            //         + Round ((Char_length(Concat(email, name)) - Char_length(REPLACE ( Concat(email,name), "second_keyword", ""))) / Char_length("second_keyword"))  AS count
            //  FROM   users
            //  Having count >0
            //  ORDER  BY count DESC;
            //         ');
            //     });
            // }
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
