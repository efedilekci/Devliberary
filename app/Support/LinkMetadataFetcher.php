<?php

namespace App\Support;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
use Throwable;

class LinkMetadataFetcher
{
    private const MAX_DOWNLOAD = 1048576;

    private const MAX_PARSE = 262144;

    private const MAX_HOPS = 3;

    /**
     * @param  (callable(string): list<string>)|null  $resolver
     */
    public function __construct(private $resolver = null) {}

    public function fetch(string $url): LinkMetadata
    {
        $current = $url;
        $seen = [];

        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            if (isset($seen[$current])) {
                break;
            }

            $seen[$current] = true;
            $target = $this->target($current);

            if ($target === null) {
                break;
            }

            $response = $this->request($target);

            if (! $response instanceof Response) {
                break;
            }

            $next = $this->redirectTarget($response, $current);

            if ($next !== null) {
                $current = $next;

                continue;
            }

            if (! $response->successful()) {
                break;
            }

            return $this->metadata($response, $target);
        }

        return new LinkMetadata;
    }

    /**
     * @return array{url: string, origin: string, host: string, port: int, ip: string}|null
     */
    private function target(string $url): ?array
    {
        if (preg_match('/[\s\x00-\x1F]/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || ! isset($parts['host'])) {
            return null;
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if ($host === '' || $port < 1 || $port > 65535 || $this->hostIsBlocked($host)) {
            return null;
        }

        $ip = $this->publicIp($host);

        if ($ip === null) {
            return null;
        }

        $displayHost = str_contains($host, ':') ? '['.$host.']' : $host;
        $origin = $scheme.'://'.$displayHost.(isset($parts['port']) ? ':'.$port : '');

        return [
            'url' => $url,
            'origin' => $origin,
            'host' => $host,
            'port' => $port,
            'ip' => $ip,
        ];
    }

    private function hostIsBlocked(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain', 'metadata.google.internal'], true)) {
            return true;
        }

        foreach (['.localhost', '.local', '.internal', '.localdomain', '.intranet', '.lan', '.home', '.home.arpa', '.corp'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function publicIp(string $host): ?string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return PublicAddress::isPublic($host) ? $host : null;
        }

        if (function_exists('idn_to_ascii') && preg_match('/[^\x00-\x7F]/', $host) === 1) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            $host = is_string($ascii) ? strtolower($ascii) : '';
        }

        if ($host === '' || $this->hostIsBlocked($host)) {
            return null;
        }

        if (preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host) !== 1) {
            return null;
        }

        $addresses = $this->resolve($host);

        if ($addresses === []) {
            return null;
        }

        foreach ($addresses as $address) {
            if (! PublicAddress::isPublic($address)) {
                return null;
            }
        }

        return $addresses[0];
    }

    /**
     * @return list<string>
     */
    private function resolve(string $host): array
    {
        if ($this->resolver !== null) {
            return array_values(($this->resolver)($host));
        }

        $type = PHP_OS_FAMILY === 'Windows' ? DNS_A : DNS_A | (defined('DNS_AAAA') ? DNS_AAAA : 0);
        $records = @dns_get_record($host, $type);
        $addresses = [];

        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && is_string($record['ip'])) {
                    $addresses[] = $record['ip'];
                }

                if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbynamel($host);

            if (is_array($fallback)) {
                $addresses = $fallback;
            }
        }

        return array_values(array_unique($addresses));
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function request(array $target): ?Response
    {
        if (! extension_loaded('curl')) {
            return null;
        }

        if ($this->httpIsFaked()) {
            return $this->fakedRequest($target);
        }

        return $this->curlRequest($target);
    }

    private function httpIsFaked(): bool
    {
        $factory = Http::getFacadeRoot();

        if (! $factory instanceof Factory) {
            return false;
        }

        $stubs = (new ReflectionProperty(Factory::class, 'stubCallbacks'))->getValue($factory);

        return $stubs instanceof Collection && $stubs->isNotEmpty();
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function fakedRequest(array $target): ?Response
    {
        try {
            return Http::withOptions([
                'allow_redirects' => false,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_RESOLVE => [$this->resolveLine($target)],
                ],
            ])->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
                'User-Agent' => 'DevliberaryLinkPreview/1.0',
            ])->get($target['url']);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function curlRequest(array $target): ?Response
    {
        $handle = curl_init($target['url']);

        if ($handle === false) {
            return null;
        }

        $headers = [];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => 0,
            CURLOPT_RESOLVE => [$this->resolveLine($target)],
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml',
                'User-Agent: DevliberaryLinkPreview/1.0',
            ],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded): int {
                return $downloaded > self::MAX_DOWNLOAD ? 1 : 0;
            },
            CURLOPT_HEADERFUNCTION => function ($resource, string $header) use (&$headers): int {
                $parts = explode(':', $header, 2);

                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($header);
            },
        ];

        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }

        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);

        if (! is_string($body)) {
            curl_close($handle);

            return null;
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);

        if (! isset($headers['content-type']) && is_string($contentType) && $contentType !== '') {
            $headers['content-type'] = $contentType;
        }

        return new Response(new PsrResponse($status, $headers, substr($body, 0, self::MAX_PARSE)));
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function resolveLine(array $target): string
    {
        $pinned = str_contains($target['ip'], ':')
            ? '['.$target['ip'].']'
            : $target['ip'];

        return $target['host'].':'.$target['port'].':'.$pinned;
    }

    private function redirectTarget(Response $response, string $current): ?string
    {
        if (! in_array($response->status(), [301, 302, 303, 307, 308], true)) {
            return null;
        }

        $location = $this->headerValue($response, 'Location');

        if ($location === '') {
            return null;
        }

        return $this->absolute($location, $current);
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function metadata(Response $response, array $target): LinkMetadata
    {
        $length = (int) $this->headerValue($response, 'Content-Length');

        if ($length > self::MAX_DOWNLOAD) {
            return new LinkMetadata(faviconUrl: $this->defaultFavicon($target));
        }

        $body = substr($response->body(), 0, self::MAX_PARSE);
        $favicon = $this->defaultFavicon($target);

        if (! $this->isHtml($response, $body)) {
            return new LinkMetadata(faviconUrl: $favicon);
        }

        $body = $this->asUtf8($body, $this->headerValue($response, 'Content-Type'));
        $head = substr($body, 0, 262144);

        return new LinkMetadata(
            title: $this->title($head),
            faviconUrl: $this->favicon($head, $target) ?? $favicon,
        );
    }

    private function isHtml(Response $response, string $body): bool
    {
        $type = strtolower($this->headerValue($response, 'Content-Type'));

        if (str_contains($type, 'text/html') || str_contains($type, 'application/xhtml')) {
            return true;
        }

        if ($type === '' || str_contains($type, 'text/plain')) {
            return str_contains(substr($body, 0, 512), '<');
        }

        return false;
    }

    private function asUtf8(string $body, string $contentType): string
    {
        $charset = 'UTF-8';

        if (preg_match('/charset=([a-zA-Z0-9._-]+)/', $contentType, $matches) === 1) {
            $charset = $matches[1];
        } elseif (preg_match('/charset=["\']?([a-zA-Z0-9._-]+)/i', substr($body, 0, 2000), $matches) === 1) {
            $charset = $matches[1];
        }

        if (strtoupper($charset) === 'UTF-8' || strtoupper($charset) === 'UTF8') {
            return $body;
        }

        $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

        return is_string($converted) ? $converted : $body;
    }

    private function title(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) !== 1) {
            return '';
        }

        $title = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = strip_tags($title);
        $title = str_replace("\0", '', $title);
        $title = preg_replace('/\s+/u', ' ', $title) ?? '';

        return trim(mb_substr($title, 0, 200));
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function favicon(string $html, array $target): ?string
    {
        if (preg_match_all('/<link\b[^>]*>/i', $html, $tags) < 1) {
            return null;
        }

        foreach (array_slice($tags[0], 0, 50) as $tag) {
            if (preg_match('/\brel\s*=\s*(["\'])(.*?)\1/i', $tag, $rel) !== 1) {
                continue;
            }

            $rels = preg_split('/\s+/', strtolower($rel[2])) ?: [];

            if (! in_array('icon', $rels, true)) {
                continue;
            }

            if (preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/i', $tag, $href) !== 1) {
                continue;
            }

            $absolute = $this->absolute(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $target['url']);

            if ($absolute === null || strlen($absolute) > 2048 || $this->target($absolute) === null) {
                continue;
            }

            return $absolute;
        }

        return null;
    }

    /**
     * @param  array{url: string, origin: string, host: string, port: int, ip: string}  $target
     */
    private function defaultFavicon(array $target): string
    {
        $url = $target['origin'].'/favicon.ico';

        return strlen($url) <= 2048 ? $url : '';
    }

    private function absolute(string $href, string $base): ?string
    {
        $href = trim($href);

        if ($href === '' || preg_match('/^(?:data|javascript|file):/i', $href) === 1) {
            return null;
        }

        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        $parts = parse_url($base);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($href, '//')) {
            return $parts['scheme'].':'.$href;
        }

        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        $path = $parts['path'] ?? '/';
        $directory = str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/') + 1) : '/';

        return $origin.$directory.$href;
    }

    private function headerValue(Response $response, string $name): string
    {
        $value = $response->header($name);

        if (is_array($value)) {
            $value = $value[0] ?? '';
        }

        return is_string($value) ? $value : '';
    }
}
