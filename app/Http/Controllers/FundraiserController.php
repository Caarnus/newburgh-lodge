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
            ->with('category:id,name,sort_order')
            ->active()
            ->leftJoin('fundraiser_categories', 'fundraiser_categories.id', '=', 'fundraisers.category_id')
            ->select('fundraisers.*')
            ->orderByRaw('CASE WHEN fundraisers.category_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('fundraiser_categories.sort_order')
            ->orderBy('fundraiser_categories.name')
            ->orderBy('fundraisers.sort_order')
            ->orderByDesc('fundraisers.created_at')
            ->get()
            ->map(fn (Fundraiser $fundraiser) => [
                'id' => $fundraiser->id,
                'title' => $fundraiser->title,
                'slug' => $fundraiser->slug,
                'sort_order' => $fundraiser->sort_order,
                'category' => $fundraiser->category ? [
                    'id' => $fundraiser->category->id,
                    'name' => $fundraiser->category->name,
                    'sort_order' => $fundraiser->category->sort_order,
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
