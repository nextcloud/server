<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\MetadataService\ValueFilter;
use function array_key_exists;
use function is_int;

class PatternAccuracyDescriptor extends AbstractDescriptor
{
    use ValueFilter;

    public function __construct(
        public readonly int $minComplexity,
        ?int $maxRetries = null,
        ?int $blockSlowdown = null
    ) {
        $minComplexity >= 0 || throw MetadataStatementLoadingException::create(
            'Invalid data. The value of "minComplexity" must be a positive integer'
        );
        parent::__construct($maxRetries, $blockSlowdown);
    }

    public static function create(int $minComplexity, ?int $maxRetries = null, ?int $blockSlowdown = null): self
    {
        return new self($minComplexity, $maxRetries, $blockSlowdown);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getMinComplexity(): int
    {
        return $this->minComplexity;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        $data = self::filterNullValues($data);
        array_key_exists('minComplexity', $data) || throw MetadataStatementLoadingException::create(
            'The key "minComplexity" is missing'
        );
        foreach (['minComplexity', 'maxRetries', 'blockSlowdown'] as $key) {
            if (array_key_exists($key, $data)) {
                is_int($data[$key]) || throw MetadataStatementLoadingException::create(
                    sprintf('Invalid data. The value of "%s" must be a positive integer', $key)
                );
            }
        }

        return self::create($data['minComplexity'], $data['maxRetries'] ?? null, $data['blockSlowdown'] ?? null);
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
            'minComplexity' => $this->minComplexity,
            'maxRetries' => $this->maxRetries,
            'blockSlowdown' => $this->blockSlowdown,
        ];

        return self::filterNullValues($data);
    }
}
