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

class DisplayPNGCharacteristicsDescriptor implements JsonSerializable
{
    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var int
     */
    private $bitDepth;

    /**
     * @var int
     */
    private $colorType;

    /**
     * @var int
     */
    private $compression;

    /**
     * @var int
     */
    private $filter;

    /**
     * @var int
     */
    private $interlace;

    /**
     * @var RgbPaletteEntry[]
     */
    private $plte = [];

    public function __construct(int $width, int $height, int $bitDepth, int $colorType, int $compression, int $filter, int $interlace)
    {
        Assertion::greaterOrEqualThan($width, 0, Utils::logicException('Invalid width'));
        Assertion::greaterOrEqualThan($height, 0, Utils::logicException('Invalid height'));
        Assertion::range($bitDepth, 0, 254, Utils::logicException('Invalid bit depth'));
        Assertion::range($colorType, 0, 254, Utils::logicException('Invalid color type'));
        Assertion::range($compression, 0, 254, Utils::logicException('Invalid compression'));
        Assertion::range($filter, 0, 254, Utils::logicException('Invalid filter'));
        Assertion::range($interlace, 0, 254, Utils::logicException('Invalid interlace'));

        $this->width = $width;
        $this->height = $height;
        $this->bitDepth = $bitDepth;
        $this->colorType = $colorType;
        $this->compression = $compression;
        $this->filter = $filter;
        $this->interlace = $interlace;
    }

    public function addPalette(RgbPaletteEntry $rgbPaletteEntry): self
    {
        $this->plte[] = $rgbPaletteEntry;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getBitDepth(): int
    {
        return $this->bitDepth;
    }

    public function getColorType(): int
    {
        return $this->colorType;
    }

    public function getCompression(): int
    {
        return $this->compression;
    }

    public function getFilter(): int
    {
        return $this->filter;
    }

    public function getInterlace(): int
    {
        return $this->interlace;
    }

    /**
     * @return RgbPaletteEntry[]
     */
    public function getPlte(): array
    {
        return $this->plte;
    }

    public static function createFromArray(array $data): self
    {
        $data = Utils::filterNullValues($data);
        foreach (['width', 'compression', 'height', 'bitDepth', 'colorType', 'compression', 'filter', 'interlace'] as $key) {
            Assertion::keyExists($data, $key, sprintf('Invalid data. The key "%s" is missing', $key));
        }
        $object = new self(
            $data['width'],
            $data['height'],
            $data['bitDepth'],
            $data['colorType'],
            $data['compression'],
            $data['filter'],
            $data['interlace']
        );
        if (isset($data['plte'])) {
            $plte = $data['plte'];
            Assertion::isArray($plte, Utils::logicException('Invalid "plte" parameter'));
            foreach ($plte as $item) {
                $object->addPalette(RgbPaletteEntry::createFromArray($item));
            }
        }

        return $object;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'width' => $this->width,
            'height' => $this->height,
            'bitDepth' => $this->bitDepth,
            'colorType' => $this->colorType,
            'compression' => $this->compression,
            'filter' => $this->filter,
            'interlace' => $this->interlace,
            'plte' => $this->plte,
        ];

        return Utils::filterNullValues($data);
    }
}
