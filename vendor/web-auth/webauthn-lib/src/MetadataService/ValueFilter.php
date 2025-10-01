<?php

declare(strict_types=1);

namespace Webauthn\MetadataService;

/**
 * @internal
 */
trait ValueFilter
{
    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function filterNullValues(array $data): array
    {
        return array_filter($data, static fn ($var): bool => $var !== null);
    }
}
