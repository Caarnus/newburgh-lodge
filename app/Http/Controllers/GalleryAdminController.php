<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GalleryAdminController extends Controller
{
    public function index(Request $request)
    {
        $selectedAlbumId = $request->integer('album');

        $albums = PhotoAlbum::query()
            ->orderBy('sort')
            ->orderByDesc('created_at')
            ->withCount(['photos as photos_count' => fn ($q) => $q->where('enabled', true)])
            ->get()
            ->map(fn (PhotoAlbum $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'description' => $a->description,
                'visibility' => $a->visibility,
                'enabled' => (bool) $a->enabled,
                'sort' => $a->sort,
                'photos_count' => $a->photos_count,
            ]);

        if (!$selectedAlbumId && $albums->count() > 0) {
            $selectedAlbumId = $albums->first()['id'];
        }

        $photos = collect();
        if ($selectedAlbumId) {
            $photos = Photo::query()
                ->where('photo_album_id', $selectedAlbumId)
                ->orderBy('sort')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Photo $p) => [
                    'id' => $p->id,
                    'photo_album_id' => $p->photo_album_id,
                    'caption' => $p->caption,
                    'alt_text' => $p->alt_text,
                    'visibility' => $p->visibility,
                    'enabled' => (bool) $p->enabled,
                    'sort' => $p->sort,
                    'url' => Storage::disk('public')->url($p->path),
                    'thumb_url' => Storage::disk('public')->url($p->thumb_path ?: $p->path),
                ]);
        }

        return Inertia::render('Admin/Gallery/Index', [
            'albums' => $albums,
            'selectedAlbumId' => $selectedAlbumId,
            'photos' => $photos,
        ]);
    }

    public function storeAlbum(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,members'],
            'enabled' => ['boolean'],
        ]);

        $slugBase = Str::slug($data['title']);
        $slug = $slugBase;
        $i = 2;
        while (PhotoAlbum::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$i++;
        }

        PhotoAlbum::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'enabled' => $data['enabled'] ?? true,
            'sort' => 0,
        ]);

        return back();
    }

    public function updateAlbum(Request $request, PhotoAlbum $album)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,members'],
            'enabled' => ['boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        // If title changes, optionally keep slug stable; or re-slug if you want.
        $album->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'enabled' => $data['enabled'] ?? $album->enabled,
            'sort' => $data['sort'] ?? $album->sort,
        ]);

        return back();
    }

    public function destroyAlbum(PhotoAlbum $album)
    {
        // Safe behavior: delete photos (files + rows) then album.
        $photos = Photo::where('photo_album_id', $album->id)->get();
        foreach ($photos as $p) {
            $this->deletePhotoFiles($p);
            $p->delete();
        }

        $album->delete();

        return back();
    }

    public function storePhoto(Request $request)
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'], // 50MB
            'photo_album_id' => ['required', 'exists:photo_albums,id'],
            'visibility' => ['nullable', 'in:public,members'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
        ]);

        $album = PhotoAlbum::findOrFail($data['photo_album_id']);

        $file = $request->file('photo');
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $path = $file->store('gallery/originals', 'public');

        // Best-effort thumbnail: if we can’t generate, fall back to original.
        $thumbPath = $this->tryMakeThumbnail($path);

        [$w, $h] = $this->safeGetImageSize(Storage::disk('public')->path($path));

        Photo::create([
            'photo_album_id' => $album->id,
            'uploaded_by' => Auth::id(),
            'visibility' => $data['visibility'] ?? $album->visibility ?? 'public',
            'path' => $path,
            'thumb_path' => $thumbPath,
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'enabled' => $data['enabled'] ?? true,
            'sort' => 0,
            'mime_type' => $mime,
            'size_bytes' => $size,
            'width' => $w,
            'height' => $h,
        ]);

        return back();
    }

    public function updatePhoto(Request $request, Photo $photo)
    {
        $data = $request->validate([
            'photo_album_id' => ['required', 'exists:photo_albums,id'],
            'visibility' => ['required', 'in:public,members'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        $photo->update([
            'photo_album_id' => $data['photo_album_id'],
            'visibility' => $data['visibility'],
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'enabled' => $data['enabled'] ?? $photo->enabled,
            'sort' => $data['sort'] ?? $photo->sort,
        ]);

        return back();
    }

    public function destroyPhoto(Photo $photo)
    {
        $this->deletePhotoFiles($photo);
        $photo->delete();

        return back();
    }

    public function reorderPhotos(Request $request)
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:photos,id'],
        ]);

        foreach ($data['ordered_ids'] as $index => $id) {
            Photo::where('id', $id)->update(['sort' => $index]);
        }

        return back();
    }

    private function deletePhotoFiles(Photo $photo): void
    {
        $disk = Storage::disk('public');
        $paths = array_filter([$photo->path, $photo->thumb_path]);
        if (!empty($paths)) {
            $disk->delete($paths);
        }
    }

    private function safeGetImageSize(string $absolutePath): array
    {
        try {
            $info = @getimagesize($absolutePath);
            if (is_array($info)) {
                return [(int) $info[0], (int) $info[1]];
            }
        } catch (\Throwable $e) {
        }
        return [null, null];
    }

    /**
     * Best-effort thumbnail:
     * - If Intervention Image is installed later, you can swap this implementation easily.
     * - For now, we just duplicate original path if we can’t thumbnail.
     */
    private function tryMakeThumbnail(string $publicDiskPath): string
    {
        // Simple fallback: use original as thumb
        // (So the UI always has a thumb_url.)
        return $publicDiskPath;
    }
}
