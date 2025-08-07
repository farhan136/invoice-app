<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Invoice App API",
 * description="Dokumentasi API untuk aplikasi invoice.",
 * @OA\Contact(
 * email="admin@example.com"
 * )
 * )
 * @OA\Tag(
 * name="Authentication",
 * description="API Endpoints for user authentication"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/login",
     * tags={"Authentication"},
     * summary="User login",
     * description="Authenticate a user and generate a Sanctum token.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email", "password"},
     * @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful authentication",
     * @OA\JsonContent(
     * @OA\Property(property="access_token", type="string", example="1|G9yA6wzPqL8xUcVd0tKjHnBmMfO..."),
     * @OA\Property(property="token_type", type="string", example="Bearer")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Invalid credentials"
     * )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * tags={"Authentication"},
     * summary="User logout",
     * description="Invalidate the user's current token.",
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Successfully logged out",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Logged out")
     * )
     * )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}