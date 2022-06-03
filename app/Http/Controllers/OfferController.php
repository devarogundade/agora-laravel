<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Offer;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfferController extends Controller
{
    # create
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->verified_at == null) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not yet verified'
            ], 200);
        }

        $asset = Asset::where('id', $request->id)->first();

        if ($user->id == $asset->user_id) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'You cannot make offer to your assets'
                ],
                200
            );
        }

        if (!$asset) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'This asset does not exist'
                ],
                200
            );
        }

        $available = $asset->unit - Utils::getOccupiedUnits($asset);
        if ($available < $request->unit) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'This asset does not have up to your requested plots. ' . $available . ' is the max unit available'
                ],
                200
            );
        }

        $offer = Offer::create([
            'duration' => $request->duration,
            'unit' => $request->unit,
            'price' => $request->price,
            'status' => 'pending',
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'expires_at' => null
        ]);

        if (!$offer) {
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
                'message' => 'Success',
                'data' => $offer
            ],
            200
        );
    }

    # accept
    public function accept(Request $request)
    {
        $user = $request->user();

        $asset = Asset::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        $offer = Offer::where('id', $request->offer_id)
            ->first();

        if (!$asset || !$offer) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Asset or offer does not exist',
                ],
                200
            );
        }

        try {
            DB::transaction(function () use ($user, $offer) {
                $renter = User::where('id', $offer->user_id)->first();
                $amount = $offer->duration * $offer->price;

                if ($amount > $renter->balance) {
                    throw new Exception($renter->name . ' do not sufficient funds');
                }

                $user->increment('balance', $amount - Utils::getFee($amount));
                $renter->decrement('balance', $amount);

                $offer->update([
                    'status' => 'accepted',
                    'expires_at' => now(),
                    'stage' => 'clearing'
                ]);
            });
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $e->getMessage()
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Offer accepted'
            ],
            200
        );
    }

    # reject
    public function cancel(Request $request)
    {
        $offer = Offer::where('id', $request->offer_id)
            ->first();

        if (!$offer) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Offer does not exist',
                ],
                200
            );
        }

        try {
            DB::transaction(function () use ($offer) {
                if ($offer->status != 'pending') {
                    throw new Exception('You can only cancel a pending offer');
                }

                $offer->update([
                    'status' => 'rejected',
                    'expires_at' => now()
                ]);
            });
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $e->getMessage()
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => 'Offer cancelled'
            ],
            200
        );
    }

    # get offers
    public function userOffers(Request $request)
    {
        $user = $request->user();

        $offers = Offer::where('user_id', $user->id)
            ->orWhereHas('asset', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('asset')
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();


        Log::debug("complete");

        if (!$offers) {
            return response()->json(
                [
                    'status' => false,
                    'message' => "Something went wrong"
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => true,
                'message' => "Result",
                'data' => $offers
            ],
            200
        );
    }
}
