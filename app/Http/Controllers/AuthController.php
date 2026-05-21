<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $admin = User::where('email', $credentials['email'])
            ->whereIn('role', ['superadmin', 'admin'])
            ->first();

        if (!$admin) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        if (!Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        if ($admin->status !== 'Aktif') {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun admin sedang nonaktif.');
        }

        $request->session()->put([
            'admin_logged_in' => true,
            'admin_user_id' => $admin->id,
            'admin_role' => $admin->role,
        ]);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();

        return redirect()->route('login');
    }
}