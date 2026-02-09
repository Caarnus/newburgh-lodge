<?php

namespace App\Http\Controllers;

use App\Mail\WebsiteContactFormMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function create()
    {
        return Inertia::render('Contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['nullable', 'string', 'max:100'],
            'email'   => ['required', 'email:rfc,dns', 'max:255'],
            'phone'   => ['required', 'string', 'min:7', 'max:25', 'regex:/^[0-9+\-\s().]{7,25}$/'],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to(['Newburgh Lodge Secretary' => 'newburgh.lodge.174@gmail.com'])
            ->send(new WebsiteContactFormMail($data));

        return back()->with('success', 'Thanks! Your message has been sent.');
    }
}
