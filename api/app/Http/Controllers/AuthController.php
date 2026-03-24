<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais estão incorretas!'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        if($user->blocked ?? 0) {
            return response()->json(['message' => 'A tua conta foi bloqueada!'], 403);
        }

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'required|string|max:50|unique:users,nickname',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $photoPath = 'anonymous.png';

        if ($request->hasFile('photo')) {
            $uploadResponse = app(FileController::class)->uploadUserPhoto($request);
            $uploadData = $uploadResponse->getData(true);
            $uploadedPath = $uploadData['photo_url'] ?? null;

            //extract just the filename from the path
            if ($uploadedPath) {
                $photoPath = basename($uploadedPath);
            }
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'password' => bcrypt($request->password),

            'photo_avatar_filename' => $photoPath,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
