<?php

namespace Database\Factories;

use App\Models\FundraiserCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FundraiserCategoryFactory extends Factory
{
    protected $model = FundraiserCategory::class;

    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
        ];
    }
}

