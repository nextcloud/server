<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;

class DisplayPNGCharacteristicsDescriptor implements JsonSerializable
{
    use ValueFilter;

    /**
     * @param RgbPaletteEntry[] $plte
     */
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly int $bitDepth,
        public readonly int $colorType,
        public readonly int $compression,
        public readonly int $filter,
        public readonly int $interlace,
        /** @readonly */
        public array $plte = [],
    ) {
        $width >= 0 || throw MetadataStatementLoadingException::create('Invalid width');
        $height >= 0 || throw MetadataStatementLoadingException::create('Invalid height');
        ($bitDepth >= 0 && $bitDepth <= 254) || throw MetadataStatementLoadingException::create('Invalid bit depth');
        ($colorType >= 0 && $colorType <= 254) || throw MetadataStatementLoadingException::create(
            'Invalid color type'
        );
        ($compression >= 0 && $compression <= 254) || throw MetadataStatementLoadingException::create(
            'Invalid compression'
        );
        ($filter >= 0 && $filter <= 254) || throw MetadataStatementLoadingException::create('Invalid filter');
        ($interlace >= 0 && $interlace <= 254) || throw MetadataStatementLoadingException::create(
            'Invalid interlace'
        );
    }

    /**
     * @param RgbPaletteEntry[] $plte
     */
    public static function create(
        int $width,
        int $height,
        int $bitDepth,
        int $colorType,
        int $compression,
        int $filter,
        int $interlace,
        array $plte = []
    ): self {
        return new self($width, $height, $bitDepth, $colorType, $compression, $filter, $interlace, $plte);
    }

    /**
     * @deprecated since 4.7.0. Please use {self::create} directly.
     * @infection-ignore-all
     */
    public function addPalettes(RgbPaletteEntry ...$rgbPaletteEntries): self
    {
        foreach ($rgbPaletteEntries as $rgbPaletteEntry) {
            $this->plte[] = $rgbPaletteEntry;
        }

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getBitDepth(): int
    {
        return $this->bitDepth;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getColorType(): int
    {
        return $this->colorType;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCompression(): int
    {
        return $this->compression;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getFilter(): int
    {
        return $this->filter;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getInterlace(): int
    {
        return $this->interlace;
    }

    /**
     * @return RgbPaletteEntry[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getPaletteEntries(): array
    {
        return $this->plte;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $data = self::filterNullValues($data);
        foreach ([
            'width',
            'compression',
            'height',
            'bitDepth',
            'colorType',
            'compression',
            'filter',
            'interlace',
        ] as $key) {
            array_key_exists($key, $data) || throw MetadataStatementLoadingException::create(sprintf(
                'Invalid data. The key "%s" is missing',
                $key
            ));
        }
        return self::create(
            $data['width'],
            $data['height'],
            $data['bitDepth'],
            $data['colorType'],
            $data['compression'],
            $data['filter'],
            $data['interlace'],
            array_map(static fn (array $item) => RgbPaletteEntry::createFromArray($item), $data['plte'] ?? [])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
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

        return self::filterNullValues($data);
    }
}
