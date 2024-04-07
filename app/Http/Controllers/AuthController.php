<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with email not found : ' . $credentials['email']
            ], 404);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Passord incorrect'
            ], 401);
        }

        $token = $user->createToken('TOKEN_NAME')->plainTextToken;
        return response()->json([
            'token_type' => 'bearer',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Unauthenticated successfully',
            'user' => $user
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'password' => 'required|string'
        ]);

        if (User::where('email', $data['email'])->exists()) {
            return response()->json([
                'message' => 'User with email already exists' . $data['email']
            ], 409);
        }

        $user = new User;
        $user->email = $data['email'];
        $user->name = $data['name'];
        $user->password = Hash::make($data['password']);
        $user->email_verified_at = now();
        $user->save();

        return response()->json($user, 201);
    }
}
