<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ─── Register ─────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user->load('activeAgreement')),
            'token' => $token,
        ], 201);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user->load('activeAgreement')),
            'token' => $token,
        ]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    // ─── Authenticated user ────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('activeAgreement')));
    }

    // ─── Update locale preference ─────────────────────────────────────────────

    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate(['locale' => 'required|in:en,bn']);

        $request->user()->update(['preferred_locale' => $request->locale]);

        return response()->json(['locale' => $request->locale]);
    }
}
