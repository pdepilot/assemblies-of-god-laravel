<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;

final class ChurchContentReadService
{
    public const SECTIONS = ['homepage_about', 'about_page'];

    /** @return array<string, mixed> */
    public function getSiteContent(): array
    {
        $defaults = $this->defaults();
        $stored = [];

        foreach (DB::table('ag_site_content')->get(['section_key', 'content_json']) as $row) {
            $decoded = json_decode((string) ($row->content_json ?? ''), true);
            if (is_array($decoded)) {
                $stored[(string) $row->section_key] = $decoded;
            }
        }

        $merged = [];
        foreach ($defaults as $key => $value) {
            $merged[$key] = isset($stored[$key])
                ? array_replace_recursive($value, $stored[$key])
                : $value;
        }

        return $merged;
    }

    /** @return array<string, mixed> */
    public function getSection(string $sectionKey): array
    {
        $content = $this->getSiteContent();

        return $content[$sectionKey] ?? [];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'homepage_about' => $this->defaultHomepageAbout(),
            'about_page' => $this->defaultAboutPage(),
        ];
    }

    /** @return array<string, mixed> */
    private function defaultHomepageAbout(): array
    {
        return [
            'eyebrow' => 'About AG Ikenebgu',
            'title' => 'Growing Together in Faith, Hope, and Love',
            'intro' => 'We are a spirit-filled Assemblies of God family committed to preaching the full Gospel, nurturing believers, and reaching our community with the love of Jesus Christ. Whether you are new to faith or returning home, you belong here.',
            'vision_title' => 'Our Vision',
            'vision_text' => 'A thriving church where every generation encounters God and lives transformed by the Gospel.',
            'mission_title' => 'Our Mission',
            'mission_text' => 'To worship God, disciple believers, strengthen families, and serve Ikenegbu with compassion and hope.',
            'gallery' => [
                ['image' => 'images/main1.jpg', 'alt' => 'Congregation worshiping at AG Ikenebgu church'],
                ['image' => 'images/rev1.jpg', 'alt' => 'Church worship gathering'],
                ['image' => 'images/rev2.jpg', 'alt' => 'Prayer and fellowship'],
            ],
            'highlight' => [
                'image' => 'images/rev1.jpg',
                'image_alt' => "Children's ministry at AG Ikenebgu",
                'quote' => 'Together we are building lives, families, and our community on the foundation of Christ.',
                'stat_value' => '500+',
                'stat_label' => 'Members',
            ],
            'features' => [
                'Worship & Prayer',
                'Family Ministries',
                'Bible Study',
                'Community Outreach',
            ],
            'scripture_banner' => [
                'cta_label' => 'Learn More',
                'cta_url' => 'about',
                'verses' => [
                    ['quote' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.', 'reference' => 'John 3:16 (KJV)'],
                    ['quote' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.', 'reference' => 'Proverbs 3:5–6 (KJV)'],
                    ['quote' => 'I can do all things through Christ which strengtheneth me.', 'reference' => 'Philippians 4:13 (KJV)'],
                    ['quote' => 'The LORD bless thee, and keep thee: the LORD make his face shine upon thee, and be gracious unto thee: the LORD lift up his countenance upon thee, and give thee peace.', 'reference' => 'Numbers 6:24–26 (KJV)'],
                    ['quote' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.', 'reference' => 'Matthew 11:28 (KJV)'],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function defaultAboutPage(): array
    {
        return [
            'hero' => [
                'title' => 'About Us',
                'breadcrumb_home_label' => 'Home',
                'breadcrumb_home_url' => './',
                'breadcrumb_parent_label' => 'Pages',
                'breadcrumb_parent_url' => '#',
                'breadcrumb_current' => 'About',
            ],
            'eyebrow' => 'About AG Ikenebgu',
            'title' => 'Growing Together in Faith, Hope, and Love',
            'intro' => 'We are a spirit-filled Assemblies of God family committed to preaching the full Gospel, nurturing believers, and reaching our community with the love of Jesus Christ.',
            'vision_title' => 'Our Vision',
            'vision_text' => 'To see lives transformed by the Gospel and communities filled with the glory of God.',
            'mission_title' => 'Our Mission',
            'mission_text' => 'To proclaim Christ, disciple believers, and serve our city with compassion and holy fire.',
            'gallery' => [
                ['image' => 'images/main1.jpg', 'alt' => 'Congregation worshiping at AG Ikenebgu church'],
                ['image' => 'images/rev1.jpg', 'alt' => 'Church worship gathering'],
                ['image' => 'images/rev2.jpg', 'alt' => 'Prayer and fellowship'],
            ],
            'highlight' => [
                'image' => 'img/about-child.jpg',
                'image_alt' => "Children's ministry at AG Ikenebgu",
                'quote' => 'Every child deserves to know they are loved by God. Your generosity helps us nurture young hearts in faith, hope, and eternal purpose.',
                'stat_value' => '$20,46',
                'stat_label' => 'Raised',
            ],
            'features' => [
                'Charity & Donation',
                'Parent Education',
                'Bible Study',
                'Community Outreach',
            ],
            'cta_banner' => [
                'title' => 'All The Gospel — Proclaiming Christ, Transforming Lives, Strengthening Our City',
                'cta_label' => 'Learn More',
                'cta_url' => '',
            ],
        ];
    }
}
