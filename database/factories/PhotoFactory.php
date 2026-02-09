<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        // These are “fake” paths for dev/test. Your upload flow will populate real paths.
        $ext = $this->faker->randomElement(['jpg', 'jpeg', 'png', 'webp']);
        $name = $this->faker->unique()->uuid();

        return [
            'photo_album_id' => PhotoAlbum::factory(),
            'uploaded_by' => User::factory(),
            'visibility' => $this->faker->randomElement(['public', 'members']),
            'path' => "gallery/originals/{$name}.{$ext}",
            'thumb_path' => "gallery/thumbs/{$name}.{$ext}",
            'caption' => $this->faker->optional()->sentence(6),
            'alt_text' => $this->faker->optional()->sentence(6),
            'taken_at' => $this->faker->optional()->dateTimeBetween('-5 years', 'now'),
            'width' => $this->faker->numberBetween(800, 4032),
            'height' => $this->faker->numberBetween(800, 4032),
            'size_bytes' => $this->faker->numberBetween(50_000, 8_000_000),
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/webp']),
            'sort' => 0,
            'enabled' => true,
        ];
    }

    public function withoutAlbum(): static
    {
        return $this->state(fn () => ['photo_album_id' => null]);
    }

    public function withoutUploader(): static
    {
        return $this->state(fn () => ['uploaded_by' => null]);
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
