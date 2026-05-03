<?php

namespace Database\Factories;

use App\Models\Fundraiser;
use App\Models\FundraiserCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FundraiserFactory extends Factory
{
    protected $model = Fundraiser::class;

    public function definition(): array
    {
        $title = Str::title($this->faker->words(4, true));
        $goal = $this->faker->numberBetween(2000, 50000);
        $raised = $this->faker->numberBetween(0, $goal);

        return [
            'title' => $title,
            'category_id' => FundraiserCategory::factory(),
            'sort_order' => $this->faker->numberBetween(0, 200),
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numerify('####'),
            'short_description' => $this->faker->sentence(16),
            'description' => $this->faker->paragraphs(3, true),
            'goal_amount' => $goal,
            'raised_amount' => $raised,
            'is_active' => true,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(30),
            'image_paths' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function startsInFuture(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addDays(3),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'ends_at' => now()->subDay(),
        ]);
    }
}
