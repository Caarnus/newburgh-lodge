<?php

namespace Database\Factories;

use App\Models\PhotoAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PhotoAlbumFactory extends Factory
{
    protected $model = PhotoAlbum::class;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numerify('####'),
            'description' => $this->faker->optional()->sentence(12),
            'visibility' => $this->faker->randomElement(['public', 'members']),
            'sort' => 0,
            'enabled' => true,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['visibility' => 'public']);
    }

    public function members(): static
    {
        return $this->state(fn () => ['visibility' => 'members']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}
