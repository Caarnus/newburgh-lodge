<?php

namespace App\Http\Controllers;

use App\Mail\SpecialRegistrationMail;
use App\Models\SpecialRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SpecialRegistrationController extends Controller
{
    public function openAir()
    {
        return Inertia::render('SpecialRegistrations/OpenAir');
    }

    public function storeOpenAir(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:40'],
        ]);

        $registration = SpecialRegistration::create([
            ...$data,
            'form' => 'open-air-2026',
        ]);

        $this->notify($registration);

        return back()->with('success', 'Thanks! Your Open Air Degree registration is received.');
    }

    public function golfScramble()
    {
        return Inertia::render('SpecialRegistrations/GolfScramble');
    }

    public function storeGolfScramble(Request $request)
    {
        $data = $request->validate([
            'registration_type' => ['required', 'in:hole_sponsor,team'],
            'name' => ['required_if:registration_type,hole_sponsor', 'nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:40'],
            'team_name' => ['required_if:registration_type,team', 'nullable', 'string', 'max:160'],
            'team_members' => ['required_if:registration_type,team', 'array', 'size:4'],
            'team_members.*' => ['required_if:registration_type,team', 'string', 'max:160'],
        ]);

        $registration = SpecialRegistration::create([
            ...$data,
            'form' => 'golf-scramble-2027',
        ]);

        $this->notify($registration);

        return back()->with('success', 'Thanks! Your golf scramble registration is received.');
    }

    public function index()
    {
        return Inertia::render('Admin/SpecialRegistrations/Index', [
            'openAirRegistrations' => SpecialRegistration::where('form', 'open-air-2026')
                ->latest()->get(),
            'golfRegistrations' => SpecialRegistration::where('form', 'golf-scramble-2027')
                ->latest()->get(),
        ]);
    }

    private function notify(SpecialRegistration $registration): void
    {
        $address = config('site.registration_notification_to');

        if ($address) {
            Mail::to($address)->send(new SpecialRegistrationMail($registration));
        }
    }
}
