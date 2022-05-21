<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Offer;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'User found',
            'data' => $user
        ], 200);
    }

    public function deposit(Request $request)
    {
        $user = $request->user();

        $user->increment('balance', $request->amount);

        return response()->json([
            'status' => true,
            'message' => 'User found',
            'data' => $user
        ], 200);
    }


    public function withdraw(Request $request)
    {
        $user = $request->user();

        if ($request->amount > $user->balance) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient funds'
            ], 200);
        }

        $user->decrement('balance', $request->amount);

        return response()->json([
            'status' => true,
            'message' => 'User found',
            'data' => $user
        ], 200);
    }

    # asset owned by a user
    public function assets(Request $request)
    {
        $user = $request->user();

        $assets = Asset::where('user_id', $user->id)
            ->get();

        $rentAssets = Offer::where('status', 'accepted')
            ->where('user_id', $user->id)
            // ->where('expires_at', '>', now()) # active only
            ->with('asset')
            ->get();

        if (!$assets) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong'
                ],
                200
            );
        }

        foreach ($assets as $asset) {
            $asset->occupied = Utils::getOccupiedUnits($asset);
        }

        $assets->rented = $rentAssets;

        return response()->json(
            [
                'status' => true,
                'message' => 'Assets found',
                'data' => $assets
            ],
            200
        );
    }
}
