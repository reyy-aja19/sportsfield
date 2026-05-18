<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('admin_logged_in')) {
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

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return back()->withInput()->with('error', 'Email atau password salah.');
        }

        if ($admin->status !== 'Aktif') {
            return back()->withInput()->with('error', 'Akun admin sedang nonaktif.');
        }

        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_user_id', $admin->id);
        $request->session()->put('admin_role', $admin->role);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin_logged_in', 'admin_user_id', 'admin_role']);
        return redirect()->route('logout.success');
    }
}
