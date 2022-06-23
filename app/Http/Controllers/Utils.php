<?php

namespace App\Http\Controllers;

use App\Models\Asset;

class Utils extends Controller
{
    public static $PLATFORM_FEE = 0.05; # percentage

    public static function getFee($amount) {
        return $amount * self::$PLATFORM_FEE;
    }

    public static function getAmount($offer) {
        return $offer->price * $offer->duration * $offer->unit;
    }

    public static function generateRandom($min = 1, $max = 20)
    {
        if (function_exists('random_int')) :
            return random_int($min, $max); # more secure
        elseif (function_exists('mt_rand')) :
            return mt_rand($min, $max); # faster
        endif;

        return rand($min, $max); # old
    }

    public static function getOccupiedUnits(Asset $asset)
    {
        $offers = $asset->offers()
            ->where('status', 'accepted')
            ->orWhere('status', 'received')
            // ->where('expires_at', '>', now()) # active only
            ->get();

        $occupied = 0;

        foreach ($offers as $offer) {
            $occupied += $offer->unit;
        }

        return $occupied;
    }

}
