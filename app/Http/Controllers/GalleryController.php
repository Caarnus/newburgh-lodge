<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        $canManage = Auth::check() && Auth::user()->can('manage-gallery');
        $canViewMembers = Auth::check()
            && (Auth::user()->can('view member photos') || $canManage);
        $visible = $canViewMembers ? ['public', 'members'] : ['public'];

        $albums = PhotoAlbum::query()
            ->where('enabled', true)
            ->whereIn('visibility', $visible)
            ->orderBy('sort')
            ->orderByDesc('created_at')
            ->withCount([
                'photos as photos_count' => fn ($q) => $q
                    ->where('enabled', true)
                    ->whereIn('visibility', $visible),
            ])
            // Pull 1 photo for cover
            ->with([
                'photos' => fn ($q) => $q
                    ->where('enabled', true)
                    ->whereIn('visibility', $visible)
                    ->orderBy('sort')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->get()
            ->map(function (PhotoAlbum $album) {
                $cover = $album->photos->first();
                $coverUrl = null;

                if ($cover) {
                    $coverUrl = Storage::disk('public')->url($cover->thumb_path ?: $cover->path);
                }

                return [
                    'id' => $album->id,
                    'title' => $album->title,
                    'slug' => $album->slug,
                    'description' => $album->description,
                    'visibility' => $album->visibility,
                    'photos_count' => $album->photos_count,
                    'cover_url' => $coverUrl,
                ];
            });

        return Inertia::render('Gallery/Index', [
            'albums' => $albums,
            'canManage' => $canManage,
        ]);
    }

    public function show(PhotoAlbum $album)
    {
        $canManage = Auth::check() && Auth::user()->can('manage-gallery');
        $canViewMembers = Auth::check()
            && (Auth::user()->can('view member photos') || $canManage);
        $visible = $canViewMembers ? ['public', 'members'] : ['public'];

        abort_unless($album->enabled, 404);

        if ($album->visibility === 'members' && !$canViewMembers) {
            abort(403);
        }

        $photos = Photo::query()
            ->where('photo_album_id', $album->id)
            ->where('enabled', true)
            ->whereIn('visibility', $visible)
            ->orderBy('sort')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Photo $p) => [
                'id' => $p->id,
                'caption' => $p->caption,
                'alt_text' => $p->alt_text,
                'visibility' => $p->visibility,
                'url' => Storage::disk('public')->url($p->path),
                'thumb_url' => Storage::disk('public')->url($p->thumb_path ?: $p->path),
                'width' => $p->width,
                'height' => $p->height,
                'created_at' => optional($p->created_at)->toISOString(),
            ]);

        return Inertia::render('Gallery/Show', [
            'album' => [
                'id' => $album->id,
                'title' => $album->title,
                'slug' => $album->slug,
                'description' => $album->description,
                'visibility' => $album->visibility,
            ],
            'photos' => $photos,
            'canManage' => $canManage,
        ]);
    }
}
