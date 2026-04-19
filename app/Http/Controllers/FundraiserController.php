<?php

namespace App\Http\Controllers;

use App\Models\Fundraiser;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FundraiserController extends Controller
{
    public function index(): Response
    {
        $fundraisers = Fundraiser::query()
            ->with('category:id,name')
            ->active()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Fundraiser $fundraiser) => [
                'id' => $fundraiser->id,
                'title' => $fundraiser->title,
                'slug' => $fundraiser->slug,
                'category' => $fundraiser->category ? [
                    'id' => $fundraiser->category->id,
                    'name' => $fundraiser->category->name,
                ] : null,
                'short_description' => $fundraiser->short_description,
                'goal_amount' => (float) $fundraiser->goal_amount,
                'raised_amount' => (float) $fundraiser->raised_amount,
                'progress_percent' => $fundraiser->progressPercent(capAtHundred: true),
            ])
            ->values();

        return Inertia::render('Public/Fundraisers/Index', [
            'fundraisers' => $fundraisers,
        ]);
    }

    public function show(Fundraiser $fundraiser): Response
    {
        abort_unless($fundraiser->isPubliclyVisible(), 404);

        return Inertia::render('Public/Fundraisers/Show', [
            'fundraiser' => [
                'id' => $fundraiser->id,
                'title' => $fundraiser->title,
                'slug' => $fundraiser->slug,
                'short_description' => $fundraiser->short_description,
                'description' => $fundraiser->description,
                'goal_amount' => (float) $fundraiser->goal_amount,
                'raised_amount' => (float) $fundraiser->raised_amount,
                'progress_percent' => $fundraiser->progressPercent(capAtHundred: true),
                'image_urls' => collect($fundraiser->image_paths ?? [])
                    ->map(fn (string $path) => Storage::url($path))
                    ->values(),
            ],
        ]);
    }
}
