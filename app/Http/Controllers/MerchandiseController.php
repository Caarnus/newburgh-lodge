<?php

namespace App\Http\Controllers;

use App\Enums\MerchandiseItemAvailability;
use App\Enums\MerchandiseOrderStatus;
use App\Mail\MerchandiseOrderRequestMail;
use App\Mail\MerchandisePreorderInterestMail;
use App\Models\MerchandiseItem;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Merchandise', [
            'items' => $this->activeItemsPayload(),
        ]);
    }

    public function checkout(): Response
    {
        return Inertia::render('MerchandiseCheckout', [
            'items' => $this->activeItemsPayload(),
            'prefillEmail' => request()->user()?->email,
        ]);
    }

    public function submitCheckout(Request $request): RedirectResponse
    {
        $activeItems = MerchandiseItem::query()
            ->active()
            ->get()
            ->keyBy('id');

        $validator = validator($request->all(), [
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'min:7', 'max:25', 'regex:/^[0-9+\-\s().]{7,25}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.size' => ['nullable', 'string', 'max:20'],
        ]);

        $validator->after(function (Validator $validator) use ($activeItems, $request): void {
            $items = $request->input('items', []);
            if (!is_array($items)) {
                return;
            }

            foreach ($items as $index => $rawItem) {
                if (!is_array($rawItem)) {
                    $validator->errors()->add("items.$index", 'Each cart item must be a valid object.');
                    continue;
                }

                $id = (int) trim((string) ($rawItem['id'] ?? ''));
                $size = trim((string) ($rawItem['size'] ?? ''));
                $quantity = (int) ($rawItem['quantity'] ?? 0);
                /** @var MerchandiseItem|null $catalogItem */
                $catalogItem = $activeItems->get($id);

                if (!$catalogItem) {
                    $validator->errors()->add("items.$index.id", 'The selected merchandise item is invalid.');
                    continue;
                }

                $this->validateItemSizeSelection($validator, $catalogItem, $size, $index);

                if ($catalogItem->availability === MerchandiseItemAvailability::OnHand->value) {
                    $stockRemaining = $catalogItem->stock_remaining;
                    if (is_numeric($stockRemaining) && $quantity > (int) $stockRemaining) {
                        $validator->errors()->add(
                            "items.$index.quantity",
                            "Only {$stockRemaining} {$catalogItem->name} item(s) are currently available.",
                        );
                    }
                }
            }
        });

        $data = $validator->validate();
        $orders = DB::transaction(function () use ($data, $request): array {
            $groupedLines = [
                MerchandiseItemAvailability::OnHand->value => [],
                MerchandiseItemAvailability::Preorder->value => [],
            ];

            foreach ($data['items'] as $index => $row) {
                /** @var MerchandiseItem|null $item */
                $item = MerchandiseItem::query()
                    ->lockForUpdate()
                    ->where('id', (int) $row['id'])
                    ->where('is_active', true)
                    ->first();

                if (!$item) {
                    throw $this->validationException("items.$index.id", 'That merchandise item is no longer available.');
                }

                $quantity = (int) $row['quantity'];
                $size = trim((string) ($row['size'] ?? ''));
                $this->validateItemSizeSelectionOrFail($item, $size, $index);
                $normalizedSize = $size === '' ? null : $size;

                if ($item->availability === MerchandiseItemAvailability::OnHand->value && !is_null($item->stock_remaining)) {
                    if ($quantity > (int) $item->stock_remaining) {
                        throw $this->validationException(
                            "items.$index.quantity",
                            "Only {$item->stock_remaining} {$item->name} item(s) are currently available.",
                        );
                    }

                    $item->stock_remaining = (int) $item->stock_remaining - $quantity;
                    $item->save();
                }

                $groupedLines[$item->availability][] = [
                    'item' => $item,
                    'quantity' => $quantity,
                    'size' => $normalizedSize,
                ];
            }

            $createdOrders = [];
            foreach ($groupedLines as $availability => $lines) {
                if ($lines === []) {
                    continue;
                }

                $order = MerchandiseOrder::query()->create([
                    'user_id' => $request->user()?->id,
                    'order_type' => $availability,
                    'status' => MerchandiseOrderStatus::Submitted->value,
                    'customer_name' => $data['name'] ?? null,
                    'customer_email' => $data['email'],
                    'customer_phone' => $data['phone'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'submitted_at' => now(),
                    'status_updated_at' => now(),
                ]);

                foreach ($lines as $line) {
                    /** @var MerchandiseItem $lineItem */
                    $lineItem = $line['item'];

                    $order->items()->create([
                        'merchandise_item_id' => $lineItem->id,
                        'item_name' => $lineItem->name,
                        'unit_price_cents' => $lineItem->price_cents,
                        'quantity' => (int) $line['quantity'],
                        'size' => $line['size'],
                    ]);
                }

                $order->load('items');
                $createdOrders[] = $order;
            }

            return $createdOrders;
        });

        foreach ($orders as $order) {
            if ($order->order_type === MerchandiseItemAvailability::OnHand->value) {
                Mail::to($this->orderRecipient())->send(new MerchandiseOrderRequestMail($order));
                continue;
            }

            Mail::to($this->orderRecipient())->send(new MerchandisePreorderInterestMail($order));
        }

        return to_route('merchandise.checkout')->with('success', 'Thanks! Your merchandise request was submitted.');
    }

    protected function orderRecipient(): array
    {
        $settings = MerchandiseSetting::query()->first();

        $email = trim((string) ($settings?->order_notification_email ?: config('site.merchandise_order_to')));
        $name = trim((string) ($settings?->order_notification_name ?: config('site.merchandise_order_name', 'Lodge Merchandise Orders')));

        if ($email === '') {
            $email = trim((string) config('site.contact_form_to', 'organization@example.com'));
        }

        return [$name => $email];
    }

    protected function validationException(string $field, string $message): ValidationException
    {
        return ValidationException::withMessages([
            $field => $message,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function activeItemsPayload(): array
    {
        return MerchandiseItem::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (MerchandiseItem $item): array => $this->presentItem($item))
            ->values()
            ->all();
    }

    protected function presentItem(MerchandiseItem $item): array
    {
        $priceCents = (int) $item->price_cents;
        $sizeOptions = $this->normalizedSizeOptions($item);
        $stockRemaining = $item->stock_remaining;

        return [
            'id' => $item->id,
            'name' => (string) $item->name,
            'description' => (string) ($item->description ?? ''),
            'image_url' => $item->image_path ? Storage::disk('public')->url($item->image_path) : null,
            'availability' => (string) $item->availability,
            'availability_label' => MerchandiseItemAvailability::label($item->availability),
            'price_cents' => $priceCents,
            'price_display' => $this->formatPrice($priceCents),
            'size_options' => $sizeOptions,
            'requires_size' => (bool) $item->requires_size && $sizeOptions !== [],
            'is_limited_edition' => (bool) $item->is_limited_edition,
            'stock_remaining' => is_numeric($stockRemaining) ? (int) $stockRemaining : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function normalizedSizeOptions(MerchandiseItem $item): array
    {
        return array_values(array_filter(array_map(
            fn ($option): string => trim((string) $option),
            $item->size_options ?? [],
        )));
    }

    protected function validateItemSizeSelection(Validator $validator, MerchandiseItem $item, string $size, int $index): void
    {
        $sizeOptions = $this->normalizedSizeOptions($item);

        if ($sizeOptions !== [] && $size === '') {
            $validator->errors()->add("items.$index.size", "Please choose a size for {$item->name}.");
        }

        if ($sizeOptions !== [] && $size !== '' && !in_array($size, $sizeOptions, true)) {
            $validator->errors()->add("items.$index.size", "The selected size for {$item->name} is invalid.");
        }

        if ($sizeOptions === [] && $size !== '') {
            $validator->errors()->add("items.$index.size", "{$item->name} does not support sizes.");
        }
    }

    protected function validateItemSizeSelectionOrFail(MerchandiseItem $item, string $size, int $index): void
    {
        $sizeOptions = $this->normalizedSizeOptions($item);

        if ($sizeOptions !== [] && $size === '') {
            throw $this->validationException("items.$index.size", "Please choose a size for {$item->name}.");
        }

        if ($sizeOptions !== [] && $size !== '' && !in_array($size, $sizeOptions, true)) {
            throw $this->validationException("items.$index.size", "The selected size for {$item->name} is invalid.");
        }

        if ($sizeOptions === [] && $size !== '') {
            throw $this->validationException("items.$index.size", "{$item->name} does not support sizes.");
        }
    }

    protected function formatPrice(int $priceCents): string
    {
        return '$' . number_format($priceCents / 100, 2);
    }
}
