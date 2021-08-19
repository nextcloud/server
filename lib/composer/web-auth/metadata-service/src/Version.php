<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\MetadataService;

use function array_key_exists;
use Assert\Assertion;
use JsonSerializable;
use LogicException;
use function Safe\sprintf;

class Version implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $major;

    /**
     * @var int|null
     */
    private $minor;

    public function __construct(?int $major, ?int $minor)
    {
        if (null === $major && null === $minor) {
            throw new LogicException('Invalid data. Must contain at least one item');
        }
        Assertion::greaterOrEqualThan($major, 0, Utils::logicException('Invalid argument "major"'));
        Assertion::greaterOrEqualThan($minor, 0, Utils::logicException('Invalid argument "minor"'));

        $this->major = $major;
        $this->minor = $minor;
    }

    public function getMajor(): ?int
    {
        return $this->major;
    }

    public function getMinor(): ?int
    {
        return $this->minor;
    }

    public static function createFromArray(array $data): self
    {
        $data = Utils::filterNullValues($data);
        foreach (['major', 'minor'] as $key) {
            if (array_key_exists($key, $data)) {
                Assertion::integer($data[$key], sprintf('Invalid value for key "%s"', $key));
            }
        }

        return new self(
            $data['major'] ?? null,
            $data['minor'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'major' => $this->major,
            'minor' => $this->minor,
        ];

        return Utils::filterNullValues($data);
    }
}
