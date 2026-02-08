<?php

namespace Database\Seeders;

use App\Models\ContentTile;
use Illuminate\Database\Seeder;

class ContentTileSeeder extends Seeder
{
    public function run(): void
    {
        $page = 'welcome';

        // Newsletter index is "/{config('site.newsletter_route')}"
        $newsletterPath = '/'.trim(config('site.newsletter_route', 'newsletters'), '/');

        // Optional: remove the old placeholder tile if it still contains lorem ipsum
        $old = ContentTile::where('slug', 'welcome-text-1')->first();
        if ($old) {
            $html = (string) (($old->config['html'] ?? '') ?? '');
            if (stripos($html, 'lorem ipsum') !== false) {
                $old->delete();
            }
        }

        /**
         * HERO: Image + intro + meeting time + link to contact
         * TileImageText.vue expects: image_url, alt, text_html, link_url, link_label, fit, object_position, show_title, show_badge
         */
        ContentTile::updateOrCreate(
            ['slug' => 'home-hero'],
            [
                'page' => $page,
                'type' => 'imageText',
                'title' => 'Newburgh Lodge #174 F&AM',
                'config' => [
                    // Using the current site’s lodge-building photo (public)
                    'image_url' => 'https://www.newburghlodge174.org/images/photo/PG4/IMG_1011.JPG',
                    'alt' => 'Newburgh Lodge #174 building',
                    'fit' => 'cover',
                    'object_position' => 'center',
                    'text_html' => implode('', [
                        '<p>Welcome to the Newburgh Lodge #174 F&amp;AM website.</p>',
                        '<p><strong>Stated Meetings:</strong> 3rd Tuesday each month (May: 3rd Wednesday · December: 1st Tuesday).</p>',
                        '<p><strong>Dinner:</strong> 6:00pm · <strong>Lodge:</strong> 7:00pm unless otherwise stated.</p>',
                        '<p class="text-sm">Freemasonry builds and advances the character of men to make them better, holding brotherly love as a paramount principle of moral integrity.</p>',
                    ]),
                    'link_url' => '/contact',
                    'link_label' => 'Contact / Visit the Lodge',
                    'show_title' => true,
                    'show_badge' => false,
                ],
                'col_start' => 1,
                'row_start' => 1,
                'col_span' => 4,
                'row_span' => 2,
                'sort' => 10,
                'enabled' => true,
            ]
        );

        ContentTile::updateOrCreate(
            ['slug' => 'home-meetings'],
            [
                'page' => $page,
                'type' => 'text',
                'title' => 'Stated Meeting Schedule',
                'config' => [
                    'html' => implode('', [
                        '<ul>',
                        '<li><strong>3rd Tuesday</strong> of every month</li>',
                        '<li><strong>May:</strong> 3rd Wednesday</li>',
                        '<li><strong>December:</strong> 1st Tuesday</li>',
                        '</ul>',
                        '<p><strong>Dinner:</strong> 6:00pm · <strong>Lodge:</strong> 7:00pm (unless otherwise stated).</p>',
                        '<p class="text-sm">See the Events page for degrees, fundraisers, and special events.</p>',
                    ]),
                    'show_title' => true,
                    'show_badge' => true,
                ],
                'col_start' => 1,
                'row_start' => 3,
                'col_span' => 2,
                'row_span' => 1,
                'sort' => 20,
                'enabled' => true,
            ]
        );

        /**
         * CONTACT & LOCATION (Links)
         * TileLinks.vue expects: items[{label,url}], show_title, show_badge
         */
        ContentTile::updateOrCreate(
            ['slug' => 'home-contact'],
            [
                'page' => $page,
                'type' => 'links',
                'title' => 'Contact & Location',
                'config' => [
                    'items' => [
                        [
                            'label' => '720 Filmore St., Newburgh, IN 47630 (Lodge location)',
                            'url' => 'https://www.google.com/maps/search/?api=1&query=720+Filmore+St+Newburgh+IN+47630',
                        ],
                        [
                            'label' => 'Do not mail to the street address — Mail: P.O. Box 490, Newburgh, IN 47629-0490',
                            'url' => 'https://www.google.com/maps/search/?api=1&query=Newburgh+IN+47629-0490+P.O.+Box+490',
                        ],
                        [
                            'label' => 'Email: newburgh.lodge.174@gmail.com',
                            'url' => 'mailto:newburgh.lodge.174@gmail.com',
                        ],
                        [
                            'label' => 'Facebook: Newburgh Lodge F&AM #174',
                            'url' => 'https://www.facebook.com/newburghlodge174/',
                        ],
                    ],
                    'show_title' => true,
                    'show_badge' => true,
                ],
                'col_start' => 3,
                'row_start' => 3,
                'col_span' => 2,
                'row_span' => 1,
                'sort' => 30,
                'enabled' => true,
            ]
        );

        /**
         * NEWSLETTER TILE (Newsletter)
         * TileNewsletter.vue expects either newsletter_id OR newsletter object. If we don’t have a seeded newsletter yet,
         * we provide a “stub” object that links to the archive route.
         */
        ContentTile::updateOrCreate(
            ['slug' => 'home-newsletter'],
            [
                'page' => $page,
                'type' => 'newsletter',
                'title' => 'Compass Points',
                'config' => [
                    'newsletter' => [
                        'id' => 0,
                        'title' => 'Compass Points Newsletter Archive',
                        'published_at' => null,
                        'excerpt' => 'View the latest issue and browse past newsletters.',
                        'cover_image_url' => null,
                        'url' => $newsletterPath,
                    ],
                    'read_label' => 'View newsletters',
                    'show_title' => true,
                    'show_badge' => false,
                    'cover_fit' => 'scale-down',
                    'object_position' => 'center',
                ],
                'col_start' => 1,
                'row_start' => 4,
                'col_span' => 2,
                'row_span' => 1,
                'sort' => 40,
                'enabled' => true,
            ]
        );

        /**
         * EVENTS CTA (CTA)
         * TileCTA.vue expects: label, url, description, show_title, show_badge
         */
        ContentTile::updateOrCreate(
            ['slug' => 'home-events-cta'],
            [
                'page' => $page,
                'type' => 'cta',
                'title' => 'Events & Calendar',
                'config' => [
                    'description' => 'See upcoming stated meetings, degrees, fundraisers, and special events.',
                    'label' => 'View events',
                    'url' => '/events',
                    'show_title' => true,
                    'show_badge' => true,
                ],
                'col_start' => 3,
                'row_start' => 4,
                'col_span' => 1,
                'row_span' => 1,
                'sort' => 50,
                'enabled' => true,
            ]
        );

        ContentTile::updateOrCreate(
            ['slug' => 'home-quick-links'],
            [
                'page' => $page,
                'type' => 'links',
                'title' => 'Quick Links',
                'config' => [
                    'items' => [
                        ['label' => 'Events', 'url' => '/events'],
                        ['label' => 'Compass Points (Newsletters)', 'url' => $newsletterPath],
                        ['label' => 'FAQ', 'url' => '/faq'],
                        ['label' => 'History', 'url' => '/history'],
                        ['label' => 'Officers', 'url' => '/officers'],
                        ['label' => 'Past Masters', 'url' => '/past-masters'],
                        ['label' => 'Contact', 'url' => '/contact'],
                    ],
                    'show_title' => true,
                    'show_badge' => false,
                ],
                'col_start' => 4,
                'row_start' => 4,
                'col_span' => 1,
                'row_span' => 1,
                'sort' => 60,
                'enabled' => true,
            ]
        );
    }
}
