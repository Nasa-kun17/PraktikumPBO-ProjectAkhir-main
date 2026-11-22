<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'data' => ['token' => $user->createToken('api')->plainTextToken],
        ], 201);
    }

    public function login(Request $r)
    {
        $data = $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Kredensial salah'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => ['token' => $user->createToken('api')->plainTextToken],
        ]);
    }
}