<?php

namespace App\Support;

/**
 * Version state + feature gating. The single source of truth for what is
 * available in "v1" (as inherited) vs "v2" (completed) — driven by the
 * Appendix 11 grades in config/nbs.php, enforced server-side.
 */
class Nbs
{
    public static function version(): string
    {
        return session('nbs.version', 'v1');
    }

    public static function isV1(): bool
    {
        return self::version() === 'v1';
    }

    public static function feature(string $key): array
    {
        return config("nbs.features.$key") ?? ['grade' => 'covered', 'label' => $key, 'processes' => []];
    }

    /** Fully usable right now? gap features are OFF in v1. */
    public static function on(string $key): bool
    {
        $f = self::feature($key);
        return ! (self::isV1() && $f['grade'] === 'gap');
    }

    /** Available but degraded in v1 (enhancement grade)? */
    public static function partial(string $key): bool
    {
        $f = self::feature($key);
        return self::isV1() && $f['grade'] === 'enhancement';
    }

    /** Chip status for a feature in the current version. */
    public static function chip(string $key): string
    {
        $f = self::feature($key);
        if (self::isV1()) {
            return match ($f['grade']) {
                'gap'         => 'gap',       // NOT IN V1
                'enhancement' => 'enh',       // PARTIAL IN V1
                default       => 'ok',        // AVAILABLE
            };
        }
        return match ($f['grade']) {
            'gap'         => 'new',           // DELIVERED BY COMPLETION
            'enhancement' => 'enh2',          // ENHANCED
            default       => 'ok',
        };
    }

    public static function processList(string $key): string
    {
        $f = self::feature($key);
        return collect($f['processes'])->map(fn ($d, $c) => "$c $d")->implode(' · ');
    }
}
