<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function deleteUser(User $user)
    {
        //o admin não pode-se apagar a si próprio
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function blockUser(User $user)
    {
        //não se pode bloquear um admin
        if ($user->type === 'A') {
            return response()->json(['message' => 'Cannot block admin users'], 403);
        }

        $user->blocked = 1;
        $user->save();

        return response()->json(['message' => 'User blocked successfully']);
    }

    public function unblockUser(User $user)
    {
        $user->blocked = 0;
        $user->save();
        
        return response()->json(['message' => 'User unblocked successfully']);
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:3',
        ]);

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'type' => 'A',
            'blocked' => 0,
        ]);

        return response()->json(['message' => 'Admin user created successfully', 'user' => $admin], 201);
    }
}