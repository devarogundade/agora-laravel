<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $keywords = explode(" ", $request->text);

        $base_query = Asset::where('id', '!=', NULL);

        foreach ($keywords as $keyword) {
            $base_query->where(function ($query) use ($keyword) {
                $query->where('type', 'like', '%' . $keyword . '%')
                    ->orWhere('state', 'like', '%' . $keyword . '%')
                    ->orWhere('location', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('price', 'like', '%' . $keyword . '%')
                    ->orWhere('metadata', 'like', '%' . $keyword . '%')
                    ->orWhere('about', 'like', '%' . $keyword . '%');
            });
        }

        foreach ($keywords as $keyword) {
            $base_query->selectRaw(
                '
        Round (
            (Char_length(Concat(location, name)) - Char_length(REPLACE ( Concat(location,name), "' . $keyword . '", ""))
        ) / Char_length("' . $keyword . '")
        ) AS count' .  $keyword
            )->orderBy("count$keyword", "desc");
        }

        $assets = $base_query->paginate(20);

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
