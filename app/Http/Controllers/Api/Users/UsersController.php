<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    // Menampilkan semua user
    public function index()
    {
        $users = UsersModel::with('company')->get(); // asumsi relasi tersedia
        return response()->json($users);
    }

    // Menyimpan user baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|uuid|exists:companies,id',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'is_admin' => 'boolean',
        ]);

        $user = UsersModel::create([
            'id' => Str::uuid(),
            'company_id' => $validated['company_id'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return response()->json($user, 201);
    }

    // Menampilkan satu user
    public function show($id)
    {
        $user = UsersModel::with('company')->findOrFail($id);
        return response()->json($user);
    }

    // Memperbarui data user
    public function update(Request $request, $id)
    {
        $user = UsersModel::findOrFail($id);

        $validated = $request->validate([
            'email' => 'sometimes|required|email|max:100|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'is_admin' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    // Menghapus user (soft delete)
    public function destroy($id)
    {
        $user = UsersModel::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}