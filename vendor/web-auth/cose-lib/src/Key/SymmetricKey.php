<?php

declare(strict_types=1);

namespace Cose\Key;

use InvalidArgumentException;

/**
 * @final
 */
class SymmetricKey extends Key
{
    final public const DATA_K = -1;

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        if (! isset($data[self::TYPE]) || (int) $data[self::TYPE] !== self::TYPE_OCT) {
            throw new InvalidArgumentException(
                'Invalid symmetric key. The key type does not correspond to a symmetric key'
            );
        }
        if (! isset($data[self::DATA_K])) {
            throw new InvalidArgumentException('Invalid symmetric key. The parameter "k" is missing');
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function create(array $data): self
    {
        return new self($data);
    }

    public function k(): string
    {
        return $this->get(self::DATA_K);
    }
}
