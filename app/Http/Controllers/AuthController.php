<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    protected $redirect = '/';
    public function signup(){
        return view('auth.signup');
    }

    public function registr(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required|unique:App\Models\User|email',
            'password'=>'required|min:6'
        ]);
        $response=[
            'name'=>$request->name,
            'email'=>request('email'),
        ];
        // return response()->json($response);
        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make(request('password')),
            'role'=>'reader',
        ]);
        $token = $user->createToken('MyAppToken')->plainTextToken;
        $user->remember_token = $token;
        $user->save();
        return redirect()->route('login');
    }

    public function login(){
        return view('auth.signin');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'=>'required|email',
            'password'=>'required|min:6'
        ]);
        if(Auth::attempt($credentials,$request->remember)){
            $request->session()->regenerate();
            return redirect()->intended('/article');
        }
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
     public function logout(Request $request): RedirectResponse
     {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
     }
}
