<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Offer;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    # create
    public function create(Request $request)
    {
        $farmer = $request->user();

        if ($farmer->email_verified_at == null) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not yet verified'
            ], 200);
        }

        $asset = Asset::where('id', $request->id)->first();

        if ($farmer->id == $asset->user_id) {
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

        $amount = ($asset->price * $request->duration * $request->unit);

        if ($farmer->balance < $amount) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'You do not have sufficient funds to place this offer'
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

        $farmer->increment('locked', $amount);
        $farmer->decrement('balance', $amount);

        $offer = Offer::create([
            'duration' => $request->duration,
            'unit' => $request->unit,
            'price' => $request->price,
            'status' => 'pending',
            'asset_id' => $asset->id,
            'user_id' => $farmer->id,
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
        $lessor = $request->user();

        $asset = Asset::where('id', $request->id)
            ->where('user_id', $lessor->id)
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
            DB::transaction(function () use ($lessor, $offer) {
                $farmer = User::where('id', $offer->user_id)->first();
                $amount = Utils::getAmount($offer);

                if ($amount > $farmer->balance) {
                    throw new Exception($farmer->name . ' do not sufficient funds');
                }

                $farmer->decrement('balance', $amount);
                $farmer->increment('locked', $amount);

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

    # received
    public function received(Request $request)
    {
        $farmer = $request->user();

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
            DB::transaction(function () use ($farmer, $offer) {
                if ($offer->status != 'accepted') {
                    throw new Exception('You can only confirm received of an accepted offer');
                }

                $lessor = $offer->asset->user();
                $amount = Utils::getAmount($offer);

                if ($amount > $farmer->locked) {
                    throw new Exception('You do not sufficient funds');
                }

                $lessor->increment('balance', $amount - Utils::getFee($amount));
                $farmer->decrement('locked', $amount);

                $offer->update([
                    'status' => 'accepted',
                    'expires_at' => now(),
                    'stage' => 'clearing'
                ]);

                $offer->update([
                    'status' => 'received'
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
                'message' => 'You have confirmed receiving this item'
            ],
            200
        );
    }

    # cancel
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
                    'status' => 'cancelled',
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

    # reject
    public function reject(Request $request)
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
                    throw new Exception('You can only reject a pending offer');
                }

                $farmer = User::where('id', $offer->user_id)->first();
                $amount = Utils::getAmount($offer);

                if ($amount >= $farmer->locked) {
                    $farmer->decrement('locked', $amount);
                } else {
                    $farmer->decrement('locked', $farmer->locked);
                }

                $farmer->increment('balance', $amount);

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
                'message' => 'Offer rejected'
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
