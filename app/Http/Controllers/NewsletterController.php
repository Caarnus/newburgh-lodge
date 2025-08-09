<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Newsletter::class);

        return Inertia::render('Newsletters/Index', [
            'newsletters' => Newsletter::orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'created_at' => $n->created_at->toDateString(),
                    'url' => route('newsletters.show', $n),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Newsletter::class);

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'issue'     => 'nullable|string|max:255',
            'summary'   => 'nullable|string',
            'body'      => 'required|string',
            'is_public' => 'boolean',
        ]);

        $newsletter = Newsletter::create([
            ...$data,
            'slug'       => \Str::slug($data['title']).'-'.now()->format('YmdHis'),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->route('compass-points.show', $newsletter)->with('success', 'Created.');
    }

    public function show(Newsletter $newsletter)
    {
        $this->authorize('view', $newsletter);

        return inertia('Newsletters/Show', [
            'newsletter' => [
                'id'         => $newsletter->id,
                'title'      => $newsletter->title,
                'slug'       => $newsletter->slug,
                'summary'    => $newsletter->summary,
                'body'       => $newsletter->body,
                'created_at' => $newsletter->created_at,
                'issue'      => $newsletter->issue,
                'author'     => $newsletter->createdBy->name ?? $newsletter->createdBy->email,
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Newsletter::class);

        return inertia('Newsletters/Upsert', [
            'newsletter' => null,
            'label' => config('site.newsletter_label'),
        ]);
    }

    public function edit(Newsletter $newsletter) {
        $this->authorize('update', $newsletter);

        return inertia('Newsletters/Upsert', [
            'newsletter' => [
                'id'         => $newsletter->id,
                'title'      => $newsletter->title,
                'issue'      => $newsletter->issue,
                'summary'    => $newsletter->summary,
                'body'       => $newsletter->body,
                'is_public'  => (bool) $newsletter->is_public,
            ],
            'label' => config('site.newsletter_label'),
        ]);
    }

    public function update(Request $request, Newsletter $newsletter)
    {
        $this->authorize('update', $newsletter);

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'issue'     => 'nullable|string|max:255',
            'summary'   => 'nullable|string',
            'body'      => 'required|string',
            'is_public' => 'boolean',
        ]);

        if ($newsletter->title !== $data['title']) {
            $newsletter->slug = \Str::slug($data['title']).'-'.now()->format('YmdHis');
        }

        $newsletter->fill($data)->save();

        return redirect()->route('compass-points.show', $newsletter)->with('success', 'Updated.');
    }

    public function destroy(Newsletter $newsletter)
    {
        $this->authorize('delete', $newsletter);

        $newsletter->delete();

        return response()->json();
    }
}
