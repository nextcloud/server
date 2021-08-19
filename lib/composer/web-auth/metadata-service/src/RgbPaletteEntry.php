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

use Assert\Assertion;
use JsonSerializable;
use function Safe\sprintf;

class RgbPaletteEntry implements JsonSerializable
{
    /**
     * @var int
     */
    private $r;

    /**
     * @var int
     */
    private $g;

    /**
     * @var int
     */
    private $b;

    public function __construct(int $r, int $g, int $b)
    {
        Assertion::range($r, 0, 255, Utils::logicException('The key "r" is invalid'));
        Assertion::range($g, 0, 255, Utils::logicException('The key "g" is invalid'));
        Assertion::range($b, 0, 255, Utils::logicException('The key "b" is invalid'));
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    public function getR(): int
    {
        return $this->r;
    }

    public function getG(): int
    {
        return $this->g;
    }

    public function getB(): int
    {
        return $this->b;
    }

    public static function createFromArray(array $data): self
    {
        foreach (['r', 'g', 'b'] as $key) {
            Assertion::keyExists($data, $key, sprintf('The key "%s" is missing', $key));
            Assertion::integer($data[$key], sprintf('The key "%s" is invalid', $key));
        }

        return new self(
            $data['r'],
            $data['g'],
            $data['b']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'r' => $this->r,
            'g' => $this->g,
            'b' => $this->b,
        ];
    }
}
