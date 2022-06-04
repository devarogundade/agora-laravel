<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Image;
use App\Models\Offer;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    # create a new asset
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->verified_at == null) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not yet verified'
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'unit' => 'required|integer',
            'price' => 'required|integer',
            'location' => 'required|string',
            'state' => 'required|string',
            'about' => 'required|string',
            'metadata' => 'required|string',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 200);
        }

        $video = $request->has('video') ? $request->video : '';

        $asset = Asset::create([
            'name' => $request->name,
            'unit' => $request->unit,
            'price' => $request->price,
            'location' => $request->location,
            'state' => $request->state,
            'about' => $request->about,
            'metadata' => $request->metadata,
            'user_id' => $user->id,
            'type' => $request->type,
            'video' => $video,
        ]);

        if (!$asset) {
            return response()->json([
                'status' => false,
                'data' => 'Failed to list asset'
            ], 200);
        }

        # upload images
        for ($index = 0; $index < 3; $index++) {
            if ($request->hasFile('image' . $index) != null) {
                $path = Storage::put('images', $request->file('image' . $index));
                $path = Storage::url($path);
                Image::create([
                    'url' => $path,
                    'asset_id' => $asset->id
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Asset has been listed',
            'data' => $asset,
        ], 200);
    }

    # delete a asset
    public function delete(Request $request)
    {
        $user = $request->user();
        $asset = $user->assets()->where('id', $request->id)->first();

        if (!$asset) {
            return response()->json([
                'status' => false,
                'message' => 'This asset does not exist',
            ], 200);
        }

        if (Utils::getOccupiedUnits($asset) > 0) {
            return response()->json([
                'status' => false,
                'message' => 'This asset has active rents. Cannot be removed',
            ], 200);
        }

        $delete = $asset->delete();

        if (!$delete) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 200);
        }


        return response()->json([
            'status' => true,
            'message' => 'Asset has been removed',
        ], 200);
    }

    # get all assets
    public function assets(Request $request)
    {
        $assets = Asset::orderBy('updated_at', 'desc');

        if ($request->type != '' && $request->type != 'all') {
            $assets = $assets->where('type', $request->type)
                ->orWhere('type', ucfirst($request->type))
                ->orWhere('type', ucwords($request->type));
        }

        if ($request->state != '' && $request->state != 'all') {
            $assets = $assets->where('state', $request->state)
                ->orWhere('state', ucfirst($request->state))
                ->orWhere('state', ucwords($request->state));
        }

        $assets = $assets->get();

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

        return response()->json(
            [
                'status' => true,
                'message' => 'Result',
                'data' => $assets
            ],
            200
        );
    }

    # get a single asset
    public function asset(Request $request)
    {
        $asset = Asset::where('id', $request->id)
            ->first();

        if (!$asset) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Asset does not exist'
                ],
                200
            );
        }

        $asset->occupied = Utils::getOccupiedUnits($asset);

        return response()->json(
            [
                'status' => true,
                'message' => 'Asset found',
                'data' => $asset
            ],
            200
        );
    }

    # location assets
    public function locationAssets(Request $request)
    {
        $assets = Asset::where('state', $request->state)
            ->orWhere('state', ucfirst($request->state))
            ->orWhere('state', ucwords($request->state))
            ->orWhere('state', trim($request->state))
            ->orWhere('state', strtolower($request->state))
            ->orderBy('updated_at', 'desc')
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

        return response()->json(
            [
                'status' => true,
                'message' => 'Result',
                'data' => $assets
            ],
            200
        );
    }

    # farm-guide
    public function farmGuide(Request $request)
    {
        $assets = Asset::where(function ($query) use ($request) {
            $query->orWhere('type', 'like', "%{$request->text}%")
                ->orWhere('state', 'like', "%{$request->text}%")
                ->orWhere('location', 'like', "%{$request->text}%")
                ->orWhere('name', 'like', "%{$request->text}%")
                ->orWhere('price', 'like', "% {$request->text} %")
                ->orWhere('metadata', 'like', "%{$request->text}%")
                ->orWhere('about', 'like', "%{$request->text}%");
        })->get()->groupBy('state');

        return $assets;
    }
}
