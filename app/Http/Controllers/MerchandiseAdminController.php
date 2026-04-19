<?php

namespace App\Http\Controllers;

use App\Enums\MerchandiseItemAvailability;
use App\Models\MerchandiseItem;
use App\Models\MerchandiseSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseAdminController extends Controller
{
    public function index(): Response
    {
        $settings = MerchandiseSetting::query()->first();

        return Inertia::render('Admin/Merchandise/Index', [
            'items' => MerchandiseItem::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (MerchandiseItem $item): array => $this->presentItem($item))
                ->all(),
            'availabilityOptions' => MerchandiseItemAvailability::options(),
            'settings' => [
                'order_notification_name' => $settings?->order_notification_name
                    ?: config('site.merchandise_order_name', 'Lodge Merchandise Orders'),
                'order_notification_email' => $settings?->order_notification_email
                    ?: config('site.merchandise_order_to')
                    ?: config('site.contact_form_to'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $uploadedImage] = $this->validateItem($request);

        if ($uploadedImage) {
            $data['image_path'] = $this->storeImage($uploadedImage);
        }

        MerchandiseItem::query()->create($data);

        return back()->with('success', 'Merchandise item created.');
    }

    public function update(Request $request, MerchandiseItem $item): RedirectResponse
    {
        [$data, $uploadedImage, $removeImage] = $this->validateItem($request);

        if ($uploadedImage) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }

            $data['image_path'] = $this->storeImage($uploadedImage);
        } elseif ($removeImage && $item->image_path) {
            Storage::disk('public')->delete($item->image_path);
            $data['image_path'] = null;
        }

        $item->update($data);

        return back()->with('success', 'Merchandise item updated.');
    }

    public function destroy(MerchandiseItem $item): RedirectResponse
    {
        if ($item->orderItems()->exists()) {
            return back()->with('success', 'Item is used in existing orders and cannot be deleted. Mark it inactive instead.');
        }

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return back()->with('success', 'Merchandise item deleted.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_notification_name' => ['nullable', 'string', 'max:120'],
            'order_notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = MerchandiseSetting::query()->firstOrNew();
        $settings->fill($data);
        $settings->save();

        return back()->with('success', 'Merchandise notification settings updated.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: UploadedFile|null, 2: bool}
     */
    protected function validateItem(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'availability' => ['required', 'string', 'in:' . implode(',', MerchandiseItemAvailability::values())],
            'price_cents' => ['required', 'integer', 'min:0', 'max:5000000'],
            'requires_size' => ['sometimes', 'boolean'],
            'size_options' => ['nullable', 'array'],
            'size_options.*' => ['string', 'max:20'],
            'is_limited_edition' => ['sometimes', 'boolean'],
            'stock_remaining' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        /** @var UploadedFile|null $uploadedImage */
        $uploadedImage = $request->file('image');
        $removeImage = (bool) ($data['remove_image'] ?? false);

        $data['requires_size'] = (bool) ($data['requires_size'] ?? false);
        $data['is_limited_edition'] = (bool) ($data['is_limited_edition'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['size_options'] = array_values(array_filter(array_map(
            fn ($size): string => trim((string) $size),
            $data['size_options'] ?? [],
        )));

        if ($data['availability'] === MerchandiseItemAvailability::Preorder->value) {
            $data['requires_size'] = false;
            $data['size_options'] = [];
            $data['stock_remaining'] = null;
        }

        if (!$data['requires_size']) {
            $data['size_options'] = [];
        }

        if (!$data['is_limited_edition']) {
            $data['stock_remaining'] = null;
        }

        unset($data['image'], $data['remove_image']);

        return [$data, $uploadedImage, $removeImage];
    }

    protected function storeImage(UploadedFile $image): string
    {
        return $image->storePublicly('merchandise/items', ['disk' => 'public']);
    }

    protected function presentItem(MerchandiseItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'image_url' => $item->image_path ? Storage::disk('public')->url($item->image_path) : null,
            'availability' => $item->availability,
            'availability_label' => MerchandiseItemAvailability::label($item->availability),
            'price_cents' => $item->price_cents,
            'price_display' => '$' . number_format($item->price_cents / 100, 2),
            'requires_size' => (bool) $item->requires_size,
            'size_options' => $item->size_options ?? [],
            'is_limited_edition' => (bool) $item->is_limited_edition,
            'stock_remaining' => $item->stock_remaining,
            'is_active' => (bool) $item->is_active,
            'sort_order' => $item->sort_order,
            'created_at' => optional($item->created_at)->toDateTimeString(),
        ];
    }
}
