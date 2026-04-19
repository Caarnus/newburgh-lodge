<?php

namespace App\Http\Controllers;

use App\Enums\MerchandiseItemAvailability;
use App\Enums\MerchandiseOrderStatus;
use App\Models\MerchandiseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseOrderAdminController extends Controller
{
    public function index(): Response
    {
        $orders = MerchandiseOrder::query()
            ->with(['items', 'user:id,name,email'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return Inertia::render('Admin/Merchandise/Orders', [
            'orders' => $orders->map(function (MerchandiseOrder $order): array {
                $totalCents = $order->items->sum(
                    fn ($item) => ((int) $item->unit_price_cents) * ((int) $item->quantity)
                );

                return [
                    'id' => $order->id,
                    'order_type' => $order->order_type,
                    'order_type_label' => MerchandiseItemAvailability::label($order->order_type),
                    'status' => $order->status,
                    'status_label' => MerchandiseOrderStatus::label($order->status),
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'notes' => $order->notes,
                    'submitted_at' => optional($order->submitted_at)->toDateTimeString(),
                    'status_updated_at' => optional($order->status_updated_at)->toDateTimeString(),
                    'created_by_user' => $order->user ? [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ] : null,
                    'total_cents' => $totalCents,
                    'total_display' => '$' . number_format($totalCents / 100, 2),
                    'items' => $order->items->map(fn ($item): array => [
                        'id' => $item->id,
                        'item_name' => $item->item_name,
                        'unit_price_cents' => $item->unit_price_cents,
                        'unit_price_display' => '$' . number_format(((int) $item->unit_price_cents) / 100, 2),
                        'quantity' => $item->quantity,
                        'size' => $item->size,
                        'line_total_display' => '$' . number_format((((int) $item->unit_price_cents) * ((int) $item->quantity)) / 100, 2),
                    ])->values()->all(),
                ];
            })->values()->all(),
            'statusOptions' => MerchandiseOrderStatus::options(),
        ]);
    }

    public function updateStatus(Request $request, MerchandiseOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', MerchandiseOrderStatus::values())],
        ]);

        $order->update([
            'status' => $data['status'],
            'status_updated_at' => now(),
        ]);

        return back()->with('success', "Order #{$order->id} status updated.");
    }
}

