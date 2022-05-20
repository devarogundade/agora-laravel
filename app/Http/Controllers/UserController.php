<?php

namespace App\Http\Controllers;

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
}
