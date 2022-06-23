<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Offer;
use App\Models\User;
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

        $assets = [];

        if ($user->purpose == 1) { // investor
            $assets = Asset::where('user_id', $user->id)
                ->get();
        } else { // farmer
            $offers = Offer::where('status', 'accepted')
                ->orWhere('status', 'received')
                ->where('user_id', $user->id)
                // ->where('expires_at', '>', now()) # active only
                ->with('asset')
                ->get();

            foreach ($offers as $offer) {
                array_push($assets, $offer->asset);
            }
        }

        if (!$assets) {
            return response()->json(
                [
                    'status' => true,
                    'message' => 'No asset found',
                    'data' => []
                ],
                200
            );
        }

        foreach ($assets as $asset) {
            $asset->occupied = Utils::getOccupiedUnits($asset);
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Assets found',
                'data' => $assets
            ],
            200
        );
    }

    public function verify(Request $request)
    {
        $user = $request->user();

        $update = User::where('id', $user->id)->first()->update([
            'email_verified_at' => now()
        ]);

        if (!$update) {
            return response()->json([
                'status' => false,
                'message' => 'Can not verify your kyc documents',
                'data' => null
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your your kyc documents are verified',
            'data' => null
        ], 200);
    }
}
