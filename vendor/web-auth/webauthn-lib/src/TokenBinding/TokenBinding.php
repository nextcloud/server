<?php

declare(strict_types=1);

namespace Webauthn\TokenBinding;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Exception\InvalidDataException;
use function array_key_exists;
use function in_array;

/**
 * @deprecated Since 4.3.0 and will be removed in 5.0.0
 * @infection-ignore-all
 */
class TokenBinding
{
    final public const TOKEN_BINDING_STATUS_PRESENT = 'present';

    final public const TOKEN_BINDING_STATUS_SUPPORTED = 'supported';

    final public const TOKEN_BINDING_STATUS_NOT_SUPPORTED = 'not-supported';

    private readonly string $status;

    private readonly ?string $id;

    public function __construct(string $status, ?string $id)
    {
        $status === self::TOKEN_BINDING_STATUS_PRESENT && $id === null && throw InvalidDataException::create(
            [$status, $id],
            'The member "id" is required when status is "present"'
        );
        $this->status = $status;
        $this->id = $id;
    }

    /**
     * @param mixed[] $json
     */
    public static function createFormArray(array $json): self
    {
        array_key_exists('status', $json) || throw InvalidDataException::create(
            $json,
            'The member "status" is required'
        );
        $status = $json['status'];
        in_array($status, self::getSupportedStatus(), true) || throw InvalidDataException::create($json, sprintf(
            'The member "status" is invalid. Supported values are: %s',
            implode(', ', self::getSupportedStatus())
        ));
        $id = array_key_exists('id', $json) ? Base64UrlSafe::decodeNoPadding($json['id']) : null;

        return new self($status, $id);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    private static function getSupportedStatus(): array
    {
        return [
            self::TOKEN_BINDING_STATUS_PRESENT,
            self::TOKEN_BINDING_STATUS_SUPPORTED,
            self::TOKEN_BINDING_STATUS_NOT_SUPPORTED,
        ];
    }
}
