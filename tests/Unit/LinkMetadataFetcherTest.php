<?php

namespace Tests\Unit;

use App\Support\LinkMetadataFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkMetadataFetcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_it_reads_the_title_and_favicon_from_a_public_page(): void
    {
        Http::fake([
            'https://example.com/guide' => Http::response(
                '<html><head><title>Laravel &amp; Docs</title><link rel="icon" href="/icon.png"></head></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
        ]);

        $metadata = $this->fetcher()->fetch('https://example.com/guide');

        $this->assertSame('Laravel & Docs', $metadata->title);
        $this->assertSame('https://example.com/icon.png', $metadata->faviconUrl);
        Http::assertSentCount(1);
    }

    public function test_it_does_not_request_a_private_or_local_address(): void
    {
        Http::fake();

        $fetcher = $this->fetcher(fn (string $host): array => $host === 'example.com' ? ['93.184.216.34'] : ['10.0.0.1']);

        $this->assertSame('', $fetcher->fetch('http://127.0.0.1/latest')->title);
        $this->assertSame('', $fetcher->fetch('http://intranet.test/secret')->title);
        $this->assertSame('', $fetcher->fetch('http://localhost/admin')->title);
        $this->assertSame('', $fetcher->fetch('https://user:secret@example.com/docs')->title);

        Http::assertNothingSent();
    }

    public function test_it_stops_when_a_public_page_redirects_to_a_private_address(): void
    {
        Http::fake([
            'https://example.com/start' => Http::response('', 302, ['Location' => 'http://127.0.0.1/latest']),
        ]);

        $metadata = $this->fetcher()->fetch('https://example.com/start');

        $this->assertSame('', $metadata->title);
        Http::assertSentCount(1);
    }

    public function test_it_rejects_a_host_that_also_resolves_to_a_private_address(): void
    {
        Http::fake();

        $metadata = $this->fetcher(fn (): array => ['1.1.1.1', '10.0.0.1'])->fetch('https://example.com/docs');

        $this->assertSame('', $metadata->title);
        Http::assertNothingSent();
    }

    public function test_it_ignores_a_favicon_that_points_at_a_private_address(): void
    {
        Http::fake([
            'https://example.com/guide' => Http::response(
                '<title>Gids</title><link rel="icon" href="http://127.0.0.1/secret.ico">',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $metadata = $this->fetcher()->fetch('https://example.com/guide');

        $this->assertSame('Gids', $metadata->title);
        $this->assertSame('https://example.com/favicon.ico', $metadata->faviconUrl);
        Http::assertSentCount(1);
    }

    public function test_it_reads_a_description_and_preview_image(): void
    {
        Http::fake([
            'https://example.com/guide' => Http::response(
                '<title>Gids</title><meta property="og:description" content="Een korte &amp; duidelijke gids."><meta property="og:image" content="/cover.jpg">',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $metadata = $this->fetcher()->fetch('https://example.com/guide');

        $this->assertSame('Een korte & duidelijke gids.', $metadata->description);
        $this->assertSame('https://example.com/cover.jpg', $metadata->previewImageUrl);
    }

    public function test_it_ignores_a_preview_image_that_points_at_a_private_address(): void
    {
        Http::fake([
            'https://example.com/guide' => Http::response(
                '<title>Gids</title><meta property="og:image" content="http://127.0.0.1/secret.png"><meta name="twitter:image" content="https://example.com/cover.png">',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $metadata = $this->fetcher()->fetch('https://example.com/guide');

        $this->assertSame('', $metadata->description);
        $this->assertSame('https://example.com/cover.png', $metadata->previewImageUrl);
    }

    /**
     * @param  (callable(string): list<string>)|null  $resolver
     */
    private function fetcher(?callable $resolver = null): LinkMetadataFetcher
    {
        return new LinkMetadataFetcher($resolver ?? fn (string $host): array => $host === 'example.com' ? ['93.184.216.34'] : ['127.0.0.1']);
    }
}
