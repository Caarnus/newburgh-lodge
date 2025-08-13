<?php

namespace Database\Seeders;

use App\Models\ContentTile;
use Illuminate\Database\Seeder;

class ContentTileSeeder extends Seeder
{
    public function run(): void
    {
        ContentTile::updateOrCreate(
            ['slug' => 'welcome-text-1'],
            [
                'page' => 'welcome',
                'type' => 'text',
                'title' => 'Welcome to Our Lodge',
                'config' => [
                    'html' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laborisnisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>', // existing text; now editable
                    'align' => 'left',
                ],
                'col_start' => 1,
                'row_start' => 1,
                'col_span' => 2,
                'row_span' => 1,
                'sort' => 10,
                'enabled' => true,
            ]
        );
    }
}
