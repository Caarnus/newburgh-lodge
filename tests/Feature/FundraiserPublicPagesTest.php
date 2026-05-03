<?php

namespace Tests\Feature;

use App\Models\Fundraiser;
use App\Models\FundraiserCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FundraiserPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_overview_only_shows_currently_active_fundraisers(): void
    {
        $visible = Fundraiser::factory()->create(['title' => 'Visible Fundraiser', 'slug' => 'visible-fundraiser']);
        Fundraiser::factory()->inactive()->create(['title' => 'Hidden Inactive']);
        Fundraiser::factory()->startsInFuture()->create(['title' => 'Future Start']);
        Fundraiser::factory()->ended()->create(['title' => 'Already Ended']);

        $response = $this->get(route('fundraisers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Fundraisers/Index')
            ->has('fundraisers', 1)
            ->where('fundraisers.0.slug', $visible->slug)
        );
    }

    public function test_public_detail_page_uses_slug_and_hides_inactive_fundraisers(): void
    {
        $visible = Fundraiser::factory()->create(['title' => 'Library Roof Fund', 'slug' => 'library-roof-fund']);
        $hidden = Fundraiser::factory()->inactive()->create(['slug' => 'hidden-fund']);

        $this->get(route('fundraisers.show', $visible->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Fundraisers/Show')
                ->where('fundraiser.slug', 'library-roof-fund')
            );

        $this->get(route('fundraisers.show', $hidden->slug))
            ->assertNotFound();
    }

    public function test_public_overview_respects_category_and_fundraiser_sort_order(): void
    {
        $categoryB = FundraiserCategory::factory()->create([
            'name' => 'Category B',
            'sort_order' => 20,
        ]);
        $categoryA = FundraiserCategory::factory()->create([
            'name' => 'Category A',
            'sort_order' => 10,
        ]);

        $aSecond = Fundraiser::factory()->create([
            'title' => 'A - Second',
            'slug' => 'a-second',
            'category_id' => $categoryA->id,
            'sort_order' => 20,
        ]);
        $aFirst = Fundraiser::factory()->create([
            'title' => 'A - First',
            'slug' => 'a-first',
            'category_id' => $categoryA->id,
            'sort_order' => 10,
        ]);
        $bOnly = Fundraiser::factory()->create([
            'title' => 'B - Only',
            'slug' => 'b-only',
            'category_id' => $categoryB->id,
            'sort_order' => 10,
        ]);

        $response = $this->get(route('fundraisers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Fundraisers/Index')
            ->has('fundraisers', 3)
            ->where('fundraisers.0.slug', $aFirst->slug)
            ->where('fundraisers.1.slug', $aSecond->slug)
            ->where('fundraisers.2.slug', $bOnly->slug)
        );
    }
}
