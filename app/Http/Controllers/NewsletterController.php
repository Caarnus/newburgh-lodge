<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Newsletter::class);

        return Newsletter::all();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Newsletter::class);

        $data = $request->validate([
            'title' => ['required'],
            'issue' => ['nullable'],
            'summary' => ['nullable'],
            'body' => ['required'],
            'is_public' => ['boolean'],
            'created_by' => ['nullable', 'exists:users'],
        ]);

        return Newsletter::create($data);
    }

    public function show(Newsletter $newsletter)
    {
        $this->authorize('view', $newsletter);

        return $newsletter;
    }

    public function update(Request $request, Newsletter $newsletter)
    {
        $this->authorize('update', $newsletter);

        $data = $request->validate([
            'title' => ['required'],
            'issue' => ['nullable'],
            'summary' => ['nullable'],
            'body' => ['required'],
            'is_public' => ['boolean'],
            'created_by' => ['nullable', 'exists:users'],
        ]);

        $newsletter->update($data);

        return $newsletter;
    }

    public function destroy(Newsletter $newsletter)
    {
        $this->authorize('delete', $newsletter);

        $newsletter->delete();

        return response()->json();
    }
}
