<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;
use function is_int;

class Version implements JsonSerializable
{
    use ValueFilter;

    public function __construct(
        public readonly ?int $major,
        public readonly ?int $minor
    ) {
        if ($major === null && $minor === null) {
            throw MetadataStatementLoadingException::create('Invalid data. Must contain at least one item');
        }
        $major >= 0 || throw MetadataStatementLoadingException::create('Invalid argument "major"');
        $minor >= 0 || throw MetadataStatementLoadingException::create('Invalid argument "minor"');
    }

    public static function create(?int $major, ?int $minor): self
    {
        return new self($major, $minor);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMajor(): ?int
    {
        return $this->major;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMinor(): ?int
    {
        return $this->minor;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $data = self::filterNullValues($data);
        foreach (['major', 'minor'] as $key) {
            if (array_key_exists($key, $data)) {
                is_int($data[$key]) || throw MetadataStatementLoadingException::create(
                    sprintf('Invalid value for key "%s"', $key)
                );
            }
        }

        return self::create($data['major'] ?? null, $data['minor'] ?? null);
    }

    /**
     * @return array<string, int|null>
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
            'major' => $this->major,
            'minor' => $this->minor,
        ];

        return self::filterNullValues($data);
    }
}
