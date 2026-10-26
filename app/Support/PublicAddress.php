<?php

namespace App\Support;

class PublicAddress
{
    /**
     * @var list<array{0: string, 1: int}>
     */
    private const BLOCKED_V4 = [
        ['0.0.0.0', 8],
        ['10.0.0.0', 8],
        ['100.64.0.0', 10],
        ['127.0.0.0', 8],
        ['169.254.0.0', 16],
        ['172.16.0.0', 12],
        ['192.0.0.0', 24],
        ['192.0.2.0', 24],
        ['192.88.99.0', 24],
        ['192.168.0.0', 16],
        ['198.18.0.0', 15],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['240.0.0.0', 4],
    ];

    public static function isPublic(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ! self::blockedV4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ! self::blockedV6($ip);
        }

        return false;
    }

    private static function blockedV4(string $ip): bool
    {
        $packed = inet_pton($ip);

        if ($packed === false) {
            return true;
        }

        $address = self::unsigned(unpack('N', $packed)[1]);

        foreach (self::BLOCKED_V4 as [$network, $bits]) {
            $networkPacked = inet_pton($network);

            if ($networkPacked === false) {
                continue;
            }

            $base = self::unsigned(unpack('N', $networkPacked)[1]);
            $mask = $bits === 0 ? 0 : self::unsigned((0xFFFFFFFF << (32 - $bits)) & 0xFFFFFFFF);

            if (($address & $mask) === ($base & $mask)) {
                return true;
            }
        }

        return false;
    }

    private static function blockedV6(string $ip): bool
    {
        $packed = inet_pton($ip);

        if ($packed === false || strlen($packed) !== 16) {
            return true;
        }

        $mapped = str_repeat("\0", 10)."\xff\xff";

        if (str_starts_with($packed, $mapped)) {
            $v4 = inet_ntop(substr($packed, 12, 4));

            return ! is_string($v4) || self::blockedV4($v4);
        }

        if (substr($packed, 0, 12) === str_repeat("\0", 12)) {
            $v4 = inet_ntop(substr($packed, 12, 4));

            return ! is_string($v4) || self::blockedV4($v4);
        }

        if (substr($packed, 0, 2) === "\x20\x02") {
            $v4 = inet_ntop(substr($packed, 2, 4));

            return ! is_string($v4) || self::blockedV4($v4);
        }

        $first = ord($packed[0]);
        $second = ord($packed[1]);

        if (($first & 0xFE) === 0xFC) {
            return true;
        }

        if ($first === 0xFE && ($second & 0xC0) === 0x80) {
            return true;
        }

        if ($first === 0xFE && ($second & 0xC0) === 0xC0) {
            return true;
        }

        if ($first === 0xFF) {
            return true;
        }

        if (substr($packed, 0, 4) === "\x20\x01\x0d\xb8") {
            return true;
        }

        if (substr($packed, 0, 4) === "\x20\x01\x00\x00") {
            return true;
        }

        if (substr($packed, 0, 8) === "\x01\x00".str_repeat("\0", 6)) {
            return true;
        }

        if (substr($packed, 0, 12) === "\x00\x64\xff\x9b".str_repeat("\0", 8)) {
            return true;
        }

        return false;
    }

    private static function unsigned(int $value): int
    {
        return (int) sprintf('%u', $value);
    }
}
