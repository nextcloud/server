<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;
use Webauthn\Exception\MetadataStatementLoadingException;
use function array_key_exists;
use function is_string;

class RogueListEntry implements JsonSerializable
{
    public function __construct(
        public readonly string $sk,
        public readonly string $date
    ) {
    }

    public static function create(string $sk, string $date): self
    {
        return new self($sk, $date);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getSk(): string
    {
        return $this->sk;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        array_key_exists('sk', $data) || throw MetadataStatementLoadingException::create('The key "sk" is missing');
        is_string($data['sk']) || throw MetadataStatementLoadingException::create('The key "date" is invalid');
        array_key_exists('date', $data) || throw MetadataStatementLoadingException::create(
            'The key "date" is missing'
        );
        is_string($data['date']) || throw MetadataStatementLoadingException::create('The key "date" is invalid');

        return self::create($data['sk'], $data['date']);
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
        return [
            'sk' => $this->sk,
            'date' => $this->date,
        ];
    }
}
