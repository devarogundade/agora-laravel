<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'https://agoralease.netlify.app';
});

# auth
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('verify-me', [AuthController::class, 'verifyMe']);

# search
Route::get('search', [SearchController::class, 'search']);

# asset
Route::get('assets', [AssetController::class, 'assets']);
Route::get('asset', [AssetController::class, 'asset']);

# location assets
Route::get('assets/location', [AssetController::class, 'locationAssets']);

# middleware
Route::middleware(['auth:sanctum'])->group(function () {
    # user
    Route::get('user', [UserController::class, 'user']);

    # create assets
    Route::post('create/asset', [AssetController::class, 'create']);

    # delete assets
    Route::get('delete/asset', [AssetController::class, 'delete']);

    # user assets
    Route::get('user/assets', [AssetController::class, 'userAssets']);

    # create offer
    Route::get('create/offer', [OfferController::class, 'create']);

    # deposit
    Route::get('deposit', [UserController::class, 'deposit']);

    # withdraw
    Route::get('withdraw', [UserController::class, 'withdraw']);

    # accept offers
    Route::get('accept/offer', [OfferController::class, 'accept']);

    # cancel offer
    Route::get('cancel/offer', [OfferController::class, 'cancel']);

    # user offers
    Route::get('user/offers', [OfferController::class, 'userOffers']);
});
