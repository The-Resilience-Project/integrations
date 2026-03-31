<?php

declare(strict_types=1);

namespace ApiV2\Infrastructure;

class RequestParser
{
    /**
     * Extract a required string field from request data.
     *
     * @param  array<string, mixed> $data
     * @throws \InvalidArgumentException
     */
    public static function requireString(array $data, string $key): string
    {
        if (!isset($data[$key]) || $data[$key] === '') {
            throw new \InvalidArgumentException("Missing required field: {$key}");
        }

        return (string) $data[$key];
    }

    /**
     * Extract an optional string field, returning null if empty/missing.
     *
     * @param array<string, mixed> $data
     */
    public static function optionalString(array $data, string $key): ?string
    {
        if (!isset($data[$key]) || $data[$key] === '') {
            return null;
        }

        return (string) $data[$key];
    }
}
