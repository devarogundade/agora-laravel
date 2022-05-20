<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'purpose' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()
                ],
                200
            );
        }

        $user = User::create([
            'name' => trim(ucwords($request->name)),
            'email' => trim($request->email),
            'password' => Hash::make($request->password),
            'purpose' => $request->purpose,
        ]);

        $token = $user->createToken('creator');
        $user->token = $token->plainTextToken;

        if ($user) {
            # sent mail to user
            # we couldn't get an email provider for free

            # AccountCreated::dispatch($user);

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Account created',
                    'data' => $user
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => false,
                'message' => 'Something went wrong'
            ],
            200
        );
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()
                ],
                200
            );
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Account with email was not found'
                ],
                200
            );
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Email or password is not correct'
                ],
                200
            );
        }

        $token = $user->createToken('accessor');
        $user->token = $token->plainTextToken;

        return response()->json(
            [
                'status' => true,
                'message' => 'User logged in successfully',
                'data' => $user
            ],
            200
        );
    }

}
