<?php

namespace App\Http\Controllers;

use App\Models\Fundraiser;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FundraiserAdminController extends Controller
{
    public function index(): InertiaResponse
    {
        $fundraisers = Fundraiser::query()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Fundraiser $fundraiser) => [
                'id' => $fundraiser->id,
                'title' => $fundraiser->title,
                'slug' => $fundraiser->slug,
                'is_active' => (bool) $fundraiser->is_active,
                'goal_amount' => (float) $fundraiser->goal_amount,
                'raised_amount' => (float) $fundraiser->raised_amount,
                'progress_percent' => $fundraiser->progressPercent(capAtHundred: true),
                'updated_at' => optional($fundraiser->updated_at)?->toIso8601String(),
                'public_url' => route('fundraisers.show', $fundraiser->slug),
                'qr_download_url' => route('admin.fundraisers.qr.download', $fundraiser->id),
            ])
            ->values();

        return Inertia::render('Fundraisers/Index', [
            'fundraisers' => $fundraisers,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Fundraisers/Upsert', [
            'fundraiser' => null,
            'qr_download_url' => null,
            'public_url' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUpsert($request);

        $fundraiser = Fundraiser::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'goal_amount' => $data['goal_amount'],
            'raised_amount' => $data['raised_amount'],
            'is_active' => $data['is_active'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'image_paths' => [],
        ]);

        $this->syncImages($request, $fundraiser, []);

        return redirect()
            ->route('admin.fundraisers.edit', $fundraiser->id)
            ->with('success', 'Fundraiser created.');
    }

    public function edit(Fundraiser $fundraiser): InertiaResponse
    {
        return Inertia::render('Fundraisers/Upsert', [
            'fundraiser' => $this->toUpsertPayload($fundraiser),
            'qr_download_url' => route('admin.fundraisers.qr.download', $fundraiser->id),
            'public_url' => route('fundraisers.show', $fundraiser->slug),
        ]);
    }

    public function update(Request $request, Fundraiser $fundraiser)
    {
        $data = $this->validateUpsert($request, $fundraiser);

        $fundraiser->fill([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'goal_amount' => $data['goal_amount'],
            'raised_amount' => $data['raised_amount'],
            'is_active' => $data['is_active'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ])->save();

        $removePaths = $data['remove_image_paths'] ?? [];
        $this->syncImages($request, $fundraiser, $removePaths);

        return back()->with('success', 'Fundraiser updated.');
    }

    public function downloadQrPng(Fundraiser $fundraiser): Response
    {
        try {
            $writer = new Writer(new GDLibRenderer(960, 4, 'png'));
            $png = $writer->writeString(route('fundraisers.show', $fundraiser->slug));
        } catch (\Throwable $throwable) {
            report($throwable);
            abort(500, 'Unable to generate QR image. Ensure the GD extension is installed.');
        }

        $filename = sprintf('fundraiser-%s-qr.png', $fundraiser->slug);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    private function validateUpsert(Request $request, ?Fundraiser $fundraiser = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string'],
            'goal_amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_paths' => ['nullable', 'array'],
            'remove_image_paths.*' => ['string'],
        ]);

        $data['slug'] = $this->ensureUniqueSlug(
            $data['slug'] ?? $data['title'],
            $fundraiser?->id
        );
        $data['goal_amount'] = round((float) $data['goal_amount'], 2);
        $data['raised_amount'] = round((float) ($data['raised_amount'] ?? 0), 2);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function ensureUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'fundraiser';
        }

        $candidate = $base;
        $suffix = 2;

        while (
            Fundraiser::query()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function syncImages(Request $request, Fundraiser $fundraiser, array $removePaths): void
    {
        $paths = collect($fundraiser->image_paths ?? []);

        $removeList = collect($removePaths)
            ->filter(fn ($path) => is_string($path))
            ->values();

        foreach ($removeList as $path) {
            if ($paths->contains($path)) {
                Storage::disk('public')->delete($path);
                $paths = $paths->reject(fn (string $existingPath) => $existingPath === $path)->values();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $storedPath = $file->storePublicly("fundraisers/{$fundraiser->id}", 'public');
                $paths->push($storedPath);
            }
        }

        $fundraiser->image_paths = $paths->unique()->values()->all();
        $fundraiser->save();
    }

    private function toUpsertPayload(Fundraiser $fundraiser): array
    {
        return [
            'id' => $fundraiser->id,
            'title' => $fundraiser->title,
            'slug' => $fundraiser->slug,
            'short_description' => $fundraiser->short_description,
            'description' => $fundraiser->description,
            'goal_amount' => (float) $fundraiser->goal_amount,
            'raised_amount' => (float) $fundraiser->raised_amount,
            'is_active' => (bool) $fundraiser->is_active,
            'starts_at' => optional($fundraiser->starts_at)?->toIso8601String(),
            'ends_at' => optional($fundraiser->ends_at)?->toIso8601String(),
            'images' => collect($fundraiser->image_paths ?? [])
                ->map(fn (string $path) => [
                    'path' => $path,
                    'url' => Storage::url($path),
                ])
                ->values(),
        ];
    }
}

