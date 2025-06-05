<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\UsersModel;

class AuthController extends Controller
{
    public function registerEmployee(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = UsersModel::create([
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }
    public function registerAdmin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = UsersModel::create([
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_admin' => true,
        ]);

        return response()->json([
            'message' => 'Admin registered successfully',
            'user' => $user
        ], 201);
    }


    // Login & generate token
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = UsersModel::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // Logout dan revoke token
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Get data user yang login
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}