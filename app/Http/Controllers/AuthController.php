<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Credenciales incorrectas'])->withInput();
        }

        session()->put('user_id', $user->id);

        return redirect('/dashboard');
    }

    public function logout()
    {
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
}
