<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // Seluruh method dilindungi oleh route middleware 'role:superadmin'

    public function index()
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:superadmin,ketua_sppg,ahli_gizi,akuntan',
            'password' => ['required', Password::min(8)],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'unit_sppg' => $data['role'] === 'superadmin' ? null : config('app.unit_sppg', 'SPPG Utama'),
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:superadmin,ketua_sppg,ahli_gizi,akuntan',
        ]);

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $menuCount = $user->menuHarians()->count();
        $importCount = $user->importLogs()->count();

        if ($menuCount > 0 || $importCount > 0) {
            return back()->with('error',
                "User tidak bisa dihapus: masih memiliki {$menuCount} menu harian dan {$importCount} riwayat import. ".
                'Data ini harus dipertahankan untuk jejak audit.'
            );
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password user berhasil direset.');
    }
}
