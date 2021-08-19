<?php
declare(strict_types=1);

namespace Doctrine\Common\Cache;

use InvalidArgumentException;

use function sprintf;

/**
 * @deprecated Deprecated without replacement in doctrine/cache 1.11. This class will be dropped in 2.0
 */
final class InvalidCacheId extends InvalidArgumentException
{
    /**
     * @param mixed $id
     */
    public static function exceedsMaxLength($id, int $maxLength): self
    {
        return new self(sprintf('Cache id "%s" exceeds maximum length %d', $id, $maxLength));
    }

    /**
     * @param mixed $id
     */
    public static function containsUnauthorizedCharacter($id, string $character): self
    {
        return new self(sprintf('Cache id "%s" contains unauthorized character "%s"', $id, $character));
    }

    /**
     * @param mixed $id
     */
    public static function containsControlCharacter($id): self
    {
        return new self(sprintf('Cache id "%s" contains at least one control character', $id));
    }
}
