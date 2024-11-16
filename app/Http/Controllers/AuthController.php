<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $redirect = '/';
    public function signup(){
        return view('auth.signup');
    }

    public function registr(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required',
            'password'=>'required|min:6'
        ]);
        $response=[
            'name'=>$request->name,
            'email'=>request('email'),
        ];
        return response()->json($response);
    }
}
