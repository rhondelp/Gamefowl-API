<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * File: tests/Feature/SeoTest.php
 *
 * Purpose:
 *   Coverage for the web pages' SEO basics: per-page <title>/description via
 *   partials/seo, noindex on the private pages (meta tag + X-Robots-Tag), and
 *   the generated /robots.txt and /sitemap.xml.
 */
class SeoTest extends TestCase
{
    private const APP_URL = 'https://seo.example.test';

    protected function setUp(): void
    {
        parent::setUp();

        // Every generated URL must come from APP_URL, so pin it to a value
        // that can't be confused with the request host.
        config(['app.url' => self::APP_URL]);
    }

    /** @return array<string, TestResponse> */
    private function allPages(): array
    {
        return [
            'home' => $this->get('/'),
            'download' => $this->get('/download/apk'),
            'reset' => $this->get('/reset-password?token=abc&email=owner@example.com'),
            'docs gate' => $this->get('/documentation'),
            'docs view' => $this->withSession(['docs_unlocked' => true])->get('/documentation/view'),
            'docs technical' => $this->withSession(['docs_unlocked' => true])->get('/documentation/technical'),
        ];
    }

    private function title(TestResponse $response): string
    {
        preg_match('#<title>(.*?)</title>#s', $response->getContent(), $m);

        return html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES);
    }

    private function description(TestResponse $response): string
    {
        preg_match('#<meta name="description" content="([^"]*)"#', $response->getContent(), $m);

        return html_entity_decode($m[1] ?? '', ENT_QUOTES);
    }

    public function test_every_page_has_a_unique_title_and_description_within_length_limits(): void
    {
        $titles = [];
        $descriptions = [];

        foreach ($this->allPages() as $name => $response) {
            $response->assertOk();

            $title = $this->title($response);
            $description = $this->description($response);

            $this->assertNotSame('', $title, "$name has no <title>");
            $this->assertNotSame('', $description, "$name has no meta description");
            $this->assertLessThanOrEqual(60, mb_strlen($title), "$name title is over 60 chars");
            $this->assertLessThanOrEqual(155, mb_strlen($description), "$name description is over 155 chars");

            $titles[$name] = $title;
            $descriptions[$name] = $description;
        }

        $this->assertSame($titles, array_unique($titles), 'Titles must be unique');
        $this->assertSame($descriptions, array_unique($descriptions), 'Descriptions must be unique');
    }

    public function test_public_pages_use_the_agreed_titles_and_are_indexable(): void
    {
        $home = $this->get('/');
        $this->assertSame('GAMEFOWL: Early Disease Monitoring for Gamefowl', $this->title($home));

        $download = $this->get('/download/apk');
        $this->assertSame('Download GAMEFOWL for Android (APK '.config('apk.version').')', $this->title($download));

        foreach ([$home, $download] as $response) {
            $response->assertDontSee('noindex', false);
            $this->assertFalse($response->headers->has('X-Robots-Tag'));
        }
    }

    public function test_head_tags_are_built_from_app_url_without_the_query_string(): void
    {
        $this->get('/reset-password?token=abc&email=owner@example.com')
            ->assertSee('<link rel="canonical" href="'.self::APP_URL.'/reset-password">', false)
            ->assertSee('<meta property="og:url" content="'.self::APP_URL.'/reset-password">', false);

        $this->get('/')
            ->assertSee('<link rel="canonical" href="'.self::APP_URL.'/">', false)
            ->assertSee('<meta property="og:site_name" content="GAMEFOWL">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('images/og-image.png', false)
            ->assertSee('<meta name="theme-color" content="#16a34a">', false);
    }

    public function test_docs_and_reset_pages_are_noindex(): void
    {
        $pages = $this->allPages();

        foreach (['reset', 'docs gate', 'docs view', 'docs technical'] as $name) {
            $pages[$name]->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        }
    }

    public function test_docs_responses_send_the_x_robots_tag_header(): void
    {
        $this->get('/documentation')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // Including the redirect a locked visitor gets.
        $this->get('/documentation/view')
            ->assertRedirect(route('docs.gate'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->withSession(['docs_unlocked' => true])->get('/documentation/view')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->withSession(['docs_unlocked' => true])->get('/documentation/technical')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_txt_is_plain_text_and_points_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));

        $lines = explode("\n", trim($response->getContent()));
        $this->assertContains('Sitemap: '.self::APP_URL.'/sitemap.xml', $lines);
        $this->assertContains('Disallow: /api/', $lines);
        $this->assertContains('Disallow: /reset-password', $lines);
        $this->assertContains('Disallow: /download/apk/file', $lines);

        // The download PAGE is in the sitemap, so it must stay crawlable,
        // and /documentation must not be advertised here.
        $this->assertNotContains('Disallow: /download/apk', $lines);
        $this->assertStringNotContainsString('/documentation', $response->getContent());
    }

    public function test_sitemap_is_valid_xml_listing_only_public_pages(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $response->getContent());

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'sitemap.xml does not parse');
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()['']);

        $entries = [];
        foreach ($xml->url as $url) {
            $entries[(string) $url->loc] = (string) $url->lastmod;
        }

        $this->assertSame([self::APP_URL.'/', self::APP_URL.'/download/apk'], array_keys($entries));
        $this->assertSame(config('apk.released_at'), $entries[self::APP_URL.'/download/apk']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $entries[self::APP_URL.'/']);

        foreach (['/documentation', '/reset-password', '/api/', '/download/apk/file'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
    }
}
