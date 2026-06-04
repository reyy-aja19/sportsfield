<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | API METHODS (Untuk Aplikasi Mobile)
    |--------------------------------------------------------------------------
    */

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Otomatis di-hash oleh properti casts di model User
            'role' => $request->role ?? 'user',
            'phone' => $request->phone,
            'status' => 'Aktif',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registrasi berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function loginApi(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }

        if ($user->status !== 'Aktif') {
            return response()->json([
                'status' => false,
                'message' => 'Akun Anda sedang nonaktif.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $user
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | WEB METHODS (Bawaan Admin Web - JANGAN DIUBAH)
    |--------------------------------------------------------------------------
    */

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