<?php

namespace App\Http\Controllers;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        Mail::raw($request->get('message'), function($message) use ($request) {
            $message->from($request->get('email'), $request->get('name'));
            $message->subject("Website Contact Form - Newburgh Lodge");
            $message->text($request->get('message'));
            $message->to('lewellym4243+lodge@gmail.com');
        });

        return response()->json('Mail sent successfully!');
    }
}
