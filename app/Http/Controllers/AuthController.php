<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Menampilkan halaman form login
    public function showLoginForm()
    {
        return view('admin.login');
    }

    // Memproses data form login yang dikirim
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Arahkan ke dashboard jika sukses
            return redirect()->intended('admin/dashboard'); 
        }

        // Kembalikan ke form jika gagal beserta pesan error
        return back()->withErrors([
            'email' => 'Email atau password salah, lek!',
        ])->onlyInput('email');
    }

    // Memproses aksi logout
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Kembalikan ke landing page setelah logout
        return redirect('/');
    }
}