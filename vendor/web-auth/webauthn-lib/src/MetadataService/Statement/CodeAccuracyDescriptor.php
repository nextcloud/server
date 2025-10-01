<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;

class CodeAccuracyDescriptor extends AbstractDescriptor
{
    use ValueFilter;

    public function __construct(
        public readonly int $base,
        public readonly int $minLength,
        ?int $maxRetries = null,
        ?int $blockSlowdown = null
    ) {
        $base >= 0 || throw MetadataStatementLoadingException::create(
            'Invalid data. The value of "base" must be a positive integer'
        );
        $minLength >= 0 || throw MetadataStatementLoadingException::create(
            'Invalid data. The value of "minLength" must be a positive integer'
        );
        parent::__construct($maxRetries, $blockSlowdown);
    }

    public static function create(int $base, int $minLength, ?int $maxRetries = null, ?int $blockSlowdown = null): self
    {
        return new self($base, $minLength, $maxRetries, $blockSlowdown);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getBase(): int
    {
        return $this->base;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        array_key_exists('base', $data) || throw MetadataStatementLoadingException::create(
            'The parameter "base" is missing'
        );
        array_key_exists('minLength', $data) || throw MetadataStatementLoadingException::create(
            'The parameter "minLength" is missing'
        );

        return self::create(
            $data['base'],
            $data['minLength'],
            $data['maxRetries'] ?? null,
            $data['blockSlowdown'] ?? null
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
            'base' => $this->base,
            'minLength' => $this->minLength,
            'maxRetries' => $this->maxRetries,
            'blockSlowdown' => $this->blockSlowdown,
        ];

        return self::filterNullValues($data);
    }
}
