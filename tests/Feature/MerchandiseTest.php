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
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Middleware\ThrottleRequests;
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

    public function test_merchandise_page_can_be_rendered(): void
    {
        $this->seedCatalog();

        $response = $this->get(route('merchandise.index'));

        $response->assertOk();
    }

    public function test_on_hand_order_creates_database_records_and_sends_email(): void
    {
        Mail::fake();
        [$polo, $coin] = $this->seedCatalog();

        $response = $this->post(route('merchandise.order'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '(812) 555-0100',
            'notes' => 'Please contact me in the afternoon.',
            'items' => [
                ['id' => $polo->id, 'quantity' => 2, 'size' => 'M'],
                ['id' => $coin->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $order = MerchandiseOrder::query()->with('items')->firstOrFail();

        $this->assertSame(MerchandiseItemAvailability::OnHand->value, $order->order_type);
        $this->assertSame(MerchandiseOrderStatus::Submitted->value, $order->status);
        $this->assertSame('john@example.com', $order->customer_email);
        $this->assertCount(2, $order->items);

        $coin->refresh();
        $this->assertSame(4, $coin->stock_remaining);

        Mail::assertSent(MerchandiseOrderRequestMail::class, function (MerchandiseOrderRequestMail $mail): bool {
            return $mail->order->customer_email === 'john@example.com'
                && $mail->order->items->count() === 2;
        });
    }

    public function test_order_requires_size_for_sized_item(): void
    {
        Mail::fake();
        [$polo] = $this->seedCatalog();

        $response = $this->from(route('merchandise.index'))->post(route('merchandise.order'), [
            'email' => 'john@example.com',
            'items' => [
                ['id' => $polo->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('merchandise.index'));
        $response->assertSessionHasErrors('items.0.size');
        Mail::assertNothingSent();
    }

    public function test_preorder_submission_records_order_and_sends_email(): void
    {
        Mail::fake();
        [, , $koozie] = $this->seedCatalog();

        $response = $this->post(route('merchandise.preorder'), [
            'item_id' => $koozie->id,
            'quantity' => 3,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'notes' => 'Interested if navy blue is available.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $order = MerchandiseOrder::query()->with('items')->firstOrFail();
        $this->assertNull($order->user_id);
        $this->assertSame(MerchandiseItemAvailability::Preorder->value, $order->order_type);
        $this->assertSame(MerchandiseOrderStatus::Submitted->value, $order->status);
        $this->assertCount(1, $order->items);
        $this->assertSame(3, $order->items->first()->quantity);

        Mail::assertSent(MerchandisePreorderInterestMail::class, function (MerchandisePreorderInterestMail $mail): bool {
            return $mail->order->customer_email === 'jane@example.com'
                && $mail->order->items->first()?->quantity === 3;
        });
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
