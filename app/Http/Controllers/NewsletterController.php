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
        $newsletters = Newsletter::latest()->paginate(10);
        return inertia('Newsletters/Index', [
            'newsletters' => $newsletters,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Newsletter::class);

        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'body' => ['required','string'],
        ]);

        Newsletter::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('newsletters.index');
    }

    public function show(Newsletter $newsletter)
    {
        $this->authorize('view', $newsletter);

        return $newsletter;
    }

    public function create(Newsletter $newsletter)
    {
        $this->authorize('create', $newsletter);
        return inertia('Newsletters/Create');
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
