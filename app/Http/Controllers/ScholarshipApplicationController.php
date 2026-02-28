<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScholarshipApplicationRequest;
use App\Mail\ScholarshipApplicationVerifyEmail;
use App\Models\ScholarshipApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ScholarshipApplicationController extends Controller
{
    public function intro()
    {
        return \Inertia\Inertia::render('Scholarship/Intro');
    }

    public function apply()
    {
        return Inertia::render('Scholarship/Apply', [
            'maxReasonChars' => 1000,
            'residencyDurationOptions' => [
                ['label' => 'Less than 1 year', 'value' => 'Less than 1 year'],
                ['label' => '1–3 years', 'value' => '1-3 years'],
                ['label' => '3–5 years', 'value' => '3-5 years'],
                ['label' => '5+ years', 'value' => '5+ years'],
            ],
            'educationLevelOptions' => [
                ['label' => 'High School', 'value' => 'High School'],
                ['label' => 'College/University', 'value' => 'College/University'],
                ['label' => 'Trade/Technical Program', 'value' => 'Trade/Technical Program'],
                ['label' => 'Other', 'value' => 'Other'],
            ],
            'lodgeRelationshipOptions' => [
                ['label' => 'None', 'value' => 'none'],
                ['label' => 'Family of a member', 'value' => 'family'],
                ['label' => 'Friend of a member', 'value' => 'friend'],
                ['label' => 'Involved with Lodge events/partners', 'value' => 'events'],
                ['label' => 'Other', 'value' => 'other'],
            ],
            'gpaScaleOptions' => [
                ['label' => '4.0 scale', 'value' => '4.0'],
                ['label' => '5.0 scale', 'value' => '5.0'],
                ['label' => '100 point', 'value' => '100'],
                ['label' => 'Other', 'value' => 'Other'],
            ],
        ]);
    }

    public function thanks(Request $request)
    {
        return Inertia::render('Scholarship/Thanks', [
            'thanksMode' => session('thanksMode', 'pending'),
        ]);
    }

    public function index()
    {
        return ScholarshipApplication::all();
    }

    public function store(ScholarshipApplicationRequest $request)
    {
        $cycleYear = (int) now()->year;

        $validated = $request->validated();
        $files = $request->file('attachments', []);

        unset($validated['attachments'], $validated['hp_field'], $validated['started_at']);

        $application = ScholarshipApplication::withTrashed()
            ->where('cycle_year', $cycleYear)
            ->where('email', $validated['email'])
            ->first();

        if ($application?->trashed()) {
            $application->restore();
        }

        if ($application && $application->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => 'An application for this email has already been verified and submitted for this cycle.',
            ]);
        }

        $contentHash = hash('sha256', strtolower(trim($validated['email'])) . '|' . trim($validated['reason']));

        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);

        if (!$application) {
            $application = new ScholarshipApplication();
        }

        $application->fill($validated);
        $application->cycle_year = $cycleYear;
        $application->status = 'pending_verification';
        $application->submitted_at = null;

        $application->content_hash = $contentHash;
        $application->ip_address = $request->ip();
        $application->user_agent = (string) $request->userAgent();

        $application->email_verification_token = $tokenHash;
        $application->email_verification_sent_at = now();
        $application->email_verified_at = null;

        $application->save();

        $stored = $application->attachments ?? [];
        foreach ($files as $file) {
            $path = $file->store("scholarships/{$cycleYear}/applications/{$application->id}", ['disk' => 'public']);
            $stored[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
            ];
        }
        $application->attachments = $stored ?: null;
        $application->save();

        $verifyUrl = URL::temporarySignedRoute(
            'scholarship.verify',
            now()->addHours(48),
            [
                'scholarshipApplication' => $application->id,
                'token' => $rawToken,
            ]
        );

        Mail::to($application->email)->send(new ScholarshipApplicationVerifyEmail($application, $verifyUrl));

        return redirect()
            ->route('scholarship.thanks')
            ->with('thanksMode', 'pending')
            ->with('success', 'Thanks! Please check your email and click the verification link to submit your application.')
            ->setStatusCode(303);
    }

    public function verify(Request $request, ScholarshipApplication $scholarshipApplication, string $token)
    {
        if (!$request->hasValidSignature()) {
            return redirect()
                ->route('scholarship.thanks')
                ->with('thanksMode', 'expired')
                ->with('error', 'This verification link has expired. Please re-submit your application to receive a new link.')
                ->setStatusCode(303);
        }

        if ($scholarshipApplication->hasVerifiedEmail()) {
            return redirect()
                ->route('scholarship.thanks')
                ->with('thanksMode', 'already_verified')
                ->with('success', 'Your application is already verified and submitted.')
                ->setStatusCode(303);
        }

        $expected = (string) $scholarshipApplication->email_verification_token;
        $actual = hash('sha256', $token);

        if (!$expected || !hash_equals($expected, $actual)) {
            return redirect()
                ->route('scholarship.thanks')
                ->with('thanksMode', 'invalid')
                ->with('error', 'Invalid verification link. Please re-submit your application to receive a new link.')
                ->setStatusCode(303);
        }

        $scholarshipApplication->email_verified_at = now();
        $scholarshipApplication->email_verification_token = null;
        $scholarshipApplication->status = 'new';
        $scholarshipApplication->submitted_at = now();
        $scholarshipApplication->save();

        // TODO: notify committee here (only after verification)

        return redirect()
            ->route('scholarship.thanks')
            ->with('thanksMode', 'verified')
            ->with('success', 'Email verified! Your scholarship application has been submitted.')
            ->setStatusCode(303);
    }

    public function show(ScholarshipApplication $scholarshipApplication)
    {
        return $scholarshipApplication;
    }

    public function update(ScholarshipApplicationRequest $request, ScholarshipApplication $scholarshipApplication)
    {
        $scholarshipApplication->update($request->validated());

        return $scholarshipApplication;
    }

    public function destroy(ScholarshipApplication $scholarshipApplication)
    {
        $scholarshipApplication->delete();

        return response()->json();
    }
}
