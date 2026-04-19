<?php

namespace Tests\Feature;

use App\Models\Fundraiser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FundraiserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_fundraiser_and_slug_is_auto_generated(): void
    {
        Permission::findOrCreate('manage-content', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('manage-content');

        $response = $this->actingAs($user)->post(route('admin.fundraisers.store'), [
            'title' => 'Roof Repair Campaign',
            'slug' => '',
            'short_description' => 'Raise funds for roof repair.',
            'description' => 'Longer detail text for the campaign.',
            'goal_amount' => 15000,
            'raised_amount' => 3200,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('fundraisers', [
            'title' => 'Roof Repair Campaign',
            'slug' => 'roof-repair-campaign',
            'goal_amount' => 15000.00,
            'raised_amount' => 3200.00,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_quickly_add_to_raised_total(): void
    {
        Permission::findOrCreate('manage-content', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('manage-content');

        $fundraiser = Fundraiser::factory()->create([
            'raised_amount' => 5000,
        ]);

        $response = $this->actingAs($user)->post(route('admin.fundraisers.raise', $fundraiser->id), [
            'amount' => 275.50,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('fundraisers', [
            'id' => $fundraiser->id,
            'raised_amount' => 5275.50,
        ]);
    }
}
