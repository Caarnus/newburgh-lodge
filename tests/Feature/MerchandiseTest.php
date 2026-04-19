<?php

namespace Tests\Feature;

use App\Enums\MerchandiseItemAvailability;
use App\Enums\MerchandiseOrderStatus;
use App\Mail\MerchandiseOrderRequestMail;
use App\Mail\MerchandisePreorderInterestMail;
use App\Models\MerchandiseItem;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseSetting;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MerchandiseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'array',
            'cache.default' => 'array',
            'permission.testing' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--path' => [
                'database/migrations/0001_01_01_000000_create_users_table.php',
                'database/migrations/2025_01_15_193949_add_two_factor_columns_to_users_table.php',
                'database/migrations/2025_01_15_194107_create_personal_access_tokens_table.php',
                'database/migrations/2025_08_09_151952_create_permission_tables.php',
                'database/migrations/2026_04_19_110000_create_merchandise_items_table.php',
                'database/migrations/2026_04_19_110100_create_merchandise_settings_table.php',
                'database/migrations/2026_04_19_110200_create_merchandise_orders_table.php',
                'database/migrations/2026_04_19_110300_create_merchandise_order_items_table.php',
                'database/migrations/2026_04_19_120000_add_image_path_to_merchandise_items_table.php',
            ],
            '--force' => true,
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withoutMiddleware(ThrottleRequests::class);

        MerchandiseSetting::query()->create([
            'order_notification_name' => 'Merch Team',
            'order_notification_email' => 'orders@example.com',
        ]);
    }

    public function test_merchandise_and_checkout_pages_can_be_rendered(): void
    {
        $this->seedCatalog();

        $this->get(route('merchandise.index'))->assertOk();
        $this->get(route('merchandise.checkout'))->assertOk();
    }

    public function test_checkout_submission_with_mixed_item_types_creates_orders_and_sends_both_email_types(): void
    {
        Mail::fake();
        [$polo, $coin, $koozie] = $this->seedCatalog();

        $response = $this->post(route('merchandise.checkout.submit'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '(812) 555-0100',
            'notes' => 'Please contact me in the afternoon.',
            'items' => [
                ['id' => $polo->id, 'quantity' => 2, 'size' => 'M'],
                ['id' => $coin->id, 'quantity' => 1, 'size' => ''],
                ['id' => $koozie->id, 'quantity' => 3, 'size' => ''],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $orders = MerchandiseOrder::query()->with('items')->orderBy('id')->get();
        $this->assertCount(2, $orders);

        $onHandOrder = $orders->firstWhere('order_type', MerchandiseItemAvailability::OnHand->value);
        $preorderOrder = $orders->firstWhere('order_type', MerchandiseItemAvailability::Preorder->value);

        $this->assertNotNull($onHandOrder);
        $this->assertNotNull($preorderOrder);
        $this->assertSame(MerchandiseOrderStatus::Submitted->value, $onHandOrder->status);
        $this->assertSame(MerchandiseOrderStatus::Submitted->value, $preorderOrder->status);
        $this->assertCount(2, $onHandOrder->items);
        $this->assertCount(1, $preorderOrder->items);
        $this->assertSame(3, (int) $preorderOrder->items->first()->quantity);

        $coin->refresh();
        $this->assertSame(4, $coin->stock_remaining);

        Mail::assertSent(MerchandiseOrderRequestMail::class, function (MerchandiseOrderRequestMail $mail): bool {
            return $mail->order->customer_email === 'john@example.com'
                && $mail->order->order_type === MerchandiseItemAvailability::OnHand->value
                && $mail->order->items->count() === 2;
        });

        Mail::assertSent(MerchandisePreorderInterestMail::class, function (MerchandisePreorderInterestMail $mail): bool {
            return $mail->order->customer_email === 'john@example.com'
                && $mail->order->order_type === MerchandiseItemAvailability::Preorder->value
                && $mail->order->items->sum('quantity') === 3;
        });
    }

    public function test_checkout_requires_size_for_sized_item(): void
    {
        Mail::fake();
        [$polo] = $this->seedCatalog();

        $response = $this->from(route('merchandise.checkout'))->post(route('merchandise.checkout.submit'), [
            'email' => 'john@example.com',
            'items' => [
                ['id' => $polo->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('merchandise.checkout'));
        $response->assertSessionHasErrors('items.0.size');
        Mail::assertNothingSent();
    }

    public function test_admin_can_update_order_status(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->withoutMiddleware(EnsureEmailIsVerified::class);
        $this->withoutMiddleware(Authorize::class);

        $this->seedCatalog();

        $order = MerchandiseOrder::query()->create([
            'order_type' => MerchandiseItemAvailability::OnHand->value,
            'status' => MerchandiseOrderStatus::Submitted->value,
            'customer_email' => 'buyer@example.com',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $response = $this->patch(
            route('admin.merchandise.orders.update-status', $order),
            ['status' => MerchandiseOrderStatus::Confirmed->value]
        );

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(MerchandiseOrderStatus::Confirmed->value, $order->status);
    }

    public function test_admin_can_delete_order_and_restore_limited_stock(): void
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->withoutMiddleware(EnsureEmailIsVerified::class);
        $this->withoutMiddleware(Authorize::class);

        [, $coin] = $this->seedCatalog();
        $coin->stock_remaining = 3;
        $coin->save();

        $order = MerchandiseOrder::query()->create([
            'order_type' => MerchandiseItemAvailability::OnHand->value,
            'status' => MerchandiseOrderStatus::Submitted->value,
            'customer_email' => 'buyer@example.com',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $order->items()->create([
            'merchandise_item_id' => $coin->id,
            'item_name' => $coin->name,
            'unit_price_cents' => $coin->price_cents,
            'quantity' => 2,
            'size' => null,
        ]);

        $response = $this->delete(route('admin.merchandise.orders.destroy', $order));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('merchandise_orders', ['id' => $order->id]);

        $coin->refresh();
        $this->assertSame(5, $coin->stock_remaining);
    }

    /**
     * @return array{0: MerchandiseItem, 1: MerchandiseItem, 2: MerchandiseItem}
     */
    protected function seedCatalog(): array
    {
        $polo = MerchandiseItem::query()->create([
            'name' => 'Polo Shirt',
            'description' => 'Embroidered polo shirt.',
            'availability' => MerchandiseItemAvailability::OnHand->value,
            'price_cents' => 4500,
            'requires_size' => true,
            'size_options' => ['S', 'M', 'L'],
            'is_limited_edition' => false,
            'stock_remaining' => null,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $coin = MerchandiseItem::query()->create([
            'name' => 'Challenge Coin',
            'description' => 'Limited edition coin.',
            'availability' => MerchandiseItemAvailability::OnHand->value,
            'price_cents' => 1500,
            'requires_size' => false,
            'size_options' => [],
            'is_limited_edition' => true,
            'stock_remaining' => 5,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $koozie = MerchandiseItem::query()->create([
            'name' => 'Lodge Koozie',
            'description' => 'Future order planning.',
            'availability' => MerchandiseItemAvailability::Preorder->value,
            'price_cents' => 700,
            'requires_size' => false,
            'size_options' => [],
            'is_limited_edition' => false,
            'stock_remaining' => null,
            'is_active' => true,
            'sort_order' => 30,
        ]);

        return [$polo, $coin, $koozie];
    }
}
