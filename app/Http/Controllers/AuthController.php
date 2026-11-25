<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Validasi input email dan password
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Normalisasi email menjadi huruf kecil
        $email = strtolower(trim($credentials['email']));

        // Cek apakah akun ada di database dan statusnya nonaktif (0)
        $user = User::where('email', $email)->first();

        if ($user && (int) $user->is_active === 0) {
            return back()
                ->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan oleh Admin.'
                ])
                ->onlyInput('email');
        }

        // Data login dengan email yang sudah dinormalisasi
        $loginCredentials = [
            'email' => $email,
            'password' => $credentials['password'],
            'is_active' => 1,
        ];

        // Proses login hanya untuk akun yang aktif
        if (Auth::attempt(
            $loginCredentials,
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Pesan jika email/password salah atau akun tidak aktif
        return back()
            ->withErrors([
                'email' => 'Email atau kata sandi salah, atau akun Anda tidak aktif.'
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('peta.index');
    }
}
