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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Cek apakah akunnya ada di database dan statusnya nonaktif (0)
        $user = User::where('email', $request->email)->first();
        if ($user && (int) $user->is_active === 0) {
            return back()
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan oleh Admin.'])
                ->onlyInput('email');
        }

        // PENTING: Tambahkan syarat 'is_active' => 1 langsung ke dalam Auth::attempt
        $loginCredentials = [
            'email' => $request->email,
            'password' => $request->password,
            'is_active' => 1, // Akun harus aktif (1) baru bisa lolos
        ];

        if (Auth::attempt($loginCredentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('dashboard'));
        }

        // Jika gagal karena password salah ATAU karena akun nonaktif yang lolos cek di atas
        return back()
            ->withErrors(['email' => 'Email atau kata sandi salah, atau akun Anda tidak aktif.'])
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