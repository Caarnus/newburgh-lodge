<?php

namespace App\Http\Controllers;

use App\Models\ContentTile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContentTileController extends Controller
{
    public function welcome()
    {
        $tiles = ContentTile::where('page', 'welcome')
            ->where('enabled', true)
            ->orderBy('sort')
            ->get();

        return Inertia::render('Welcome', [
            'tiles' => $tiles,
            'grid' => ['cols' => 4, 'gap'=> '1rem'],
        ]);
    }

    public function index(Request $request)
    {
        $page = $request->query('page') ?? 'welcome';

        return Inertia::render('Admin/HomeLayout', [
            'tiles' => ContentTile::where('page', $page)->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title'] ?? $data['type'].'-'.Str::random(10));
        $data['page'] = $data['page'] ?? 'welcome';
        ContentTile::create($data);

        return back()->with(['success' => 'Tile created successfully.']);
    }

    public function update(Request $request, ContentTile $tile)
    {
        $tile->update($this->validated($request));
        return back()->with('success', 'Tile updated.');
    }

    public function destroy(ContentTile $tile)
    {
        $tile->delete();
        return back()->with('success', 'Tile deleted.');
    }

    public function reorder(Request $request)
    {
        //expects [{id, sort, col_start, row_start, col_span, row_span}, ...]
        foreach ($request->input('tiles',[]) as $tile) {
            ContentTile::where('id', $tile['id'] ?? null)->update([
                'sort' => $tile['sort'] ?? 0,
                'col_start' => $tile['col_start'] ?? 1,
                'row_start' => $tile['row_start'] ?? 1,
                'col_span' => $tile['col_span'] ?? 1,
                'row_span' => $tile['row_span'] ?? 1,
            ]);
        }

        return back()->with('success', 'Layout updated.');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required','image','max:4096'],
        ]);
        $path = $request->file('file')->storePublicly('tiles', ['disk' => 'public']);
        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    private function validated(Request $request): array
    {
        $type = $request->input('type');

        $baseRules  = $request->validate([
            'page' => ['sometimes','string'],
            'type' => ['required','string', Rule::in(['text','newsletter','image_text','image','links','events','cta'])],
            'slug' => ['required','string','unique:content_tiles,slug'],
            'title' => ['nullable','string'],
            'enabled' => ['sometimes','boolean'],
            'sort' => ['sometimes','integer'],
            'col_start' => ['sometimes','integer','min:1'],
            'row_start' => ['sometimes','integer','min:1'],
            'col_span' => ['sometimes','integer','min:1','max:12'],
            'row_span' => ['sometimes','integer','min:1','max:12'],
            'config' => ['nullable','array'],
        ]);

        // Per-type rules for config.* (only the keys we allow)
        $perType = match ($type) {
            'text' => [
                'config.html'  => ['nullable','string'],
                'config.align' => ['nullable', Rule::in(['left','center','right'])],
            ],
            'newsletter' => [
                'config.cover_image_url' => ['nullable','url'],
                'config.issue_title'     => ['nullable','string','max:255'],
                'config.issue_date'      => ['nullable','date'],
                'config.summary_html'    => ['nullable','string'],
                'config.link_url'        => ['nullable','url'],
                'config.link_label'      => ['nullable','string','max:100'],
            ],
            'image_text' => [
                'config.image_url'  => ['nullable','url'],
                'config.alt'        => ['nullable','string','max:255'],
                'config.text_html'  => ['nullable','string'],
                'config.link_url'   => ['nullable','url'],
                'config.link_label' => ['nullable','string','max:100'],
            ],
            'image' => [
                'config.image_url' => ['nullable','url'],
                'config.alt'       => ['nullable','string','max:255'],
                'config.caption'   => ['nullable','string','max:255'],
                'config.link_url'  => ['nullable','url'],
            ],
            'links' => [
                'config.items'           => ['nullable','array','max:20'],
                'config.items.*.label'   => ['required_with:config.items','string','max:120'],
                'config.items.*.url'     => ['required_with:config.items','url','max:2048'],
            ],
            'cta' => [
                'config.label'       => ['nullable','string','max:80'],
                'config.url'         => ['nullable','url','max:2048'],
                'config.description' => ['nullable','string','max:600'],
            ],
            'events' => [
                'config.days_ahead'  => ['nullable','integer','min:1','max:365'],
                'config.categories'  => ['nullable','array','max:20'],
                'config.categories.*'=> ['string','max:80'],
                'config.limit'       => ['nullable','integer','min:1','max:20'],
                'config.endpoint'    => ['nullable','url'], // optional override for fetch URL
            ],
            default => [],
        };

        // Validate
        $rules = array_merge($baseRules, $perType);
        $data  = Validator::make($request->all(), $rules)->validate();

        // Whitelisted config keys per type → we’ll prune everything else
        $whitelists = [
            'text' => ['html','align'],
            'newsletter' => ['cover_image_url','issue_title','issue_date','summary_html','link_url','link_label'],
            'image_text' => ['image_url','alt','text_html','link_url','link_label'],
            'image' => ['image_url','alt','caption','link_url'],
            'links' => ['items'],
            'cta' => ['label','url','description'],
            'events' => ['days_ahead','categories','limit','endpoint'],
        ];

        if (isset($data['config']) && isset($whitelists[$type])) {
            $allowed = $whitelists[$type];

            $clean = [];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data['config'])) {
                    $clean[$key] = $data['config'][$key];
                }
            }
            $data['config'] = $clean;
        }

        return $data;
    }
}
