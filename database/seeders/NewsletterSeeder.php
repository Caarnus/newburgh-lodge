<?php

namespace Database\Seeders;

use App\Models\Newsletter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsletterSeeder extends Seeder
{
    public function run(): void
    {
        $newsletters = [
            [
                'title' => 'April 2024 Compass Points',
                'content' => view('newsletters.sample', [
                    'title' => 'April 2024 Compass Points',
                    'body' => 'Welcome to our April edition! Here is the latest news and updates...'
                ])->render(),
                'created_at' => Carbon::parse('2024-04-14 16:38:21'),
            ],
            [
                'title' => 'May 2024 Compass Points',
                'content' => view('newsletters.sample', [
                    'title' => 'May 2024 Compass Points',
                    'body' => 'This month we celebrate...'
                ])->render(),
                'created_at' => Carbon::parse('2024-05-14 09:00:00'),
            ],
        ];

        foreach ($newsletters as $data) {
            Newsletter::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'body' => $data['content'],
                'created_at' => $data['created_at'],
            ]);
        }
    }
}
