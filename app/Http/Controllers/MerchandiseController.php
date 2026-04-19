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
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseController extends Controller
{
    public function index(): Response
    {
        $items = MerchandiseItem::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Merchandise', [
            'availableItems' => $items
                ->where('availability', MerchandiseItemAvailability::OnHand->value)
                ->values()
                ->map(fn (MerchandiseItem $item): array => $this->presentItem($item))
                ->all(),
            'preorderItems' => $items
                ->where('availability', MerchandiseItemAvailability::Preorder->value)
                ->values()
                ->map(fn (MerchandiseItem $item): array => $this->presentItem($item))
                ->all(),
            'prefillEmail' => request()->user()?->email,
        ]);
    }

    public function submitOrder(Request $request): RedirectResponse
    {
        $availableItems = MerchandiseItem::query()
            ->active()
            ->onHand()
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

        $validator->after(function (Validator $validator) use ($availableItems, $request): void {
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
                $catalogItem = $availableItems->get($id);

                if (!$catalogItem) {
                    $validator->errors()->add("items.$index.id", 'The selected merchandise item is invalid.');
                    continue;
                }

                $sizeOptions = array_values(array_filter(array_map(
                    fn ($option): string => trim((string) $option),
                    $catalogItem->size_options ?? [],
                )));

                if ($sizeOptions !== [] && $size === '') {
                    $validator->errors()->add("items.$index.size", "Please choose a size for {$catalogItem->name}.");
                }

                if ($sizeOptions !== [] && $size !== '' && !in_array($size, $sizeOptions, true)) {
                    $validator->errors()->add("items.$index.size", "The selected size for {$catalogItem->name} is invalid.");
                }

                if ($sizeOptions === [] && $size !== '') {
                    $validator->errors()->add("items.$index.size", "{$catalogItem->name} does not support sizes.");
                }

                $stockRemaining = $catalogItem->stock_remaining;
                if (is_numeric($stockRemaining) && $quantity > (int) $stockRemaining) {
                    $validator->errors()->add(
                        "items.$index.quantity",
                        "Only {$stockRemaining} {$catalogItem->name} item(s) are currently available.",
                    );
                }
            }
        });

        $data = $validator->validate();

        $order = DB::transaction(function () use ($data, $request): MerchandiseOrder {
            $order = MerchandiseOrder::query()->create([
                'user_id' => $request->user()?->id,
                'order_type' => MerchandiseItemAvailability::OnHand->value,
                'status' => MerchandiseOrderStatus::Submitted->value,
                'customer_name' => $data['name'] ?? null,
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'status_updated_at' => now(),
            ]);

            foreach ($data['items'] as $index => $row) {
                $item = MerchandiseItem::query()
                    ->lockForUpdate()
                    ->where('id', (int) $row['id'])
                    ->where('availability', MerchandiseItemAvailability::OnHand->value)
                    ->where('is_active', true)
                    ->first();

                if (!$item) {
                    throw $this->validationException("items.$index.id", 'That merchandise item is no longer available.');
                }

                $quantity = (int) $row['quantity'];
                $size = trim((string) ($row['size'] ?? ''));
                $size = $size === '' ? null : $size;

                if (!is_null($item->stock_remaining)) {
                    if ($quantity > (int) $item->stock_remaining) {
                        throw $this->validationException(
                            "items.$index.quantity",
                            "Only {$item->stock_remaining} {$item->name} item(s) are currently available.",
                        );
                    }

                    $item->stock_remaining = (int) $item->stock_remaining - $quantity;
                    $item->save();
                }

                $order->items()->create([
                    'merchandise_item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_price_cents' => $item->price_cents,
                    'quantity' => $quantity,
                    'size' => $size,
                ]);
            }

            return $order;
        });

        $order->load('items');

        Mail::to($this->orderRecipient())
            ->send(new MerchandiseOrderRequestMail($order));

        return back()->with('success', 'Thanks! Your merchandise order request has been sent.');
    }

    public function submitPreorder(Request $request): RedirectResponse
    {
        $preorderItems = MerchandiseItem::query()
            ->active()
            ->preorder()
            ->get()
            ->keyBy('id');

        $validator = validator($request->all(), [
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'item_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validator->after(function (Validator $validator) use ($preorderItems, $request): void {
            $itemId = (int) trim((string) $request->input('item_id', ''));
            if ($itemId === 0) {
                return;
            }

            if (!$preorderItems->has($itemId)) {
                $validator->errors()->add('item_id', 'The selected pre-order item is invalid.');
            }
        });

        $data = $validator->validate();
        /** @var MerchandiseItem $item */
        $item = $preorderItems->get((int) $data['item_id']);

        $order = DB::transaction(function () use ($data, $item, $request): MerchandiseOrder {
            $order = MerchandiseOrder::query()->create([
                'user_id' => $request->user()?->id,
                'order_type' => MerchandiseItemAvailability::Preorder->value,
                'status' => MerchandiseOrderStatus::Submitted->value,
                'customer_name' => $data['name'] ?? null,
                'customer_email' => $data['email'],
                'customer_phone' => null,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'status_updated_at' => now(),
            ]);

            $order->items()->create([
                'merchandise_item_id' => $item->id,
                'item_name' => $item->name,
                'unit_price_cents' => $item->price_cents,
                'quantity' => (int) $data['quantity'],
                'size' => null,
            ]);

            return $order;
        });

        $order->load('items');

        Mail::to($this->orderRecipient())
            ->send(new MerchandisePreorderInterestMail($order));

        return back()->with('success', 'Thanks! Your pre-order interest was submitted.');
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

    protected function presentItem(MerchandiseItem $item): array
    {
        $priceCents = (int) $item->price_cents;
        $sizeOptions = array_values(array_filter(array_map(
            fn ($option): string => trim((string) $option),
            $item->size_options ?? [],
        )));
        $stockRemaining = $item->stock_remaining;

        return [
            'id' => $item->id,
            'name' => (string) $item->name,
            'description' => (string) ($item->description ?? ''),
            'price_cents' => $priceCents,
            'price_display' => $this->formatPrice($priceCents),
            'size_options' => $sizeOptions,
            'requires_size' => (bool) $item->requires_size && $sizeOptions !== [],
            'is_limited_edition' => (bool) $item->is_limited_edition,
            'stock_remaining' => is_numeric($stockRemaining) ? (int) $stockRemaining : null,
        ];
    }

    protected function formatPrice(int $priceCents): string
    {
        return '$' . number_format($priceCents / 100, 2);
    }
}
