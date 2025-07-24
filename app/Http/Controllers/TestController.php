<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TestController extends Controller
{
    public function index(){
        $user= User::where('id',36)->first();
        // dd($user);
        Mail::to("helloabhay007@gmail.com")->queue(new WelcomeMail($user)); 
        // Mail::to("helloabhay007@gmail.com")->send(new WelcomeMail($user));       
    }
}
