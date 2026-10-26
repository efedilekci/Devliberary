<?php

namespace Tests\Unit;

use App\Support\PublicAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicAddressTest extends TestCase
{
    #[DataProvider('addresses')]
    public function test_it_accepts_only_public_addresses(string $ip, bool $public): void
    {
        $this->assertSame($public, PublicAddress::isPublic($ip));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function addresses(): array
    {
        return [
            'dns' => ['8.8.8.8', true],
            'cloudflare' => ['1.1.1.1', true],
            'just outside private' => ['11.0.0.1', true],
            'just outside shared 172' => ['172.32.0.1', true],
            'unspecified' => ['0.0.0.0', false],
            'private 10' => ['10.1.2.3', false],
            'cgnat' => ['100.64.1.1', false],
            'loopback' => ['127.0.0.2', false],
            'link local' => ['169.254.169.254', false],
            'private 172' => ['172.31.255.255', false],
            'documentation' => ['192.0.2.1', false],
            'private 192' => ['192.168.1.1', false],
            'benchmarking' => ['198.18.0.1', false],
            'multicast' => ['224.0.0.1', false],
            'reserved' => ['240.0.0.1', false],
            'broadcast' => ['255.255.255.255', false],
            'ipv6 public' => ['2606:4700:4700::1111', true],
            'ipv6 loopback' => ['::1', false],
            'ipv6 unique local' => ['fc00::1', false],
            'ipv6 ula fd' => ['fd00::1', false],
            'ipv6 link local' => ['fe80::1', false],
            'ipv6 documentation' => ['2001:db8::1', false],
            'mapped loopback' => ['::ffff:127.0.0.1', false],
            'mapped public' => ['::ffff:8.8.8.8', true],
            'sixtofour loopback' => ['2002:7f00:1::', false],
            'sixtofour public' => ['2002:808:808::', true],
            'not an address' => ['example.com', false],
        ];
    }
}
