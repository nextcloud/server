<?php

declare(strict_types=1);

namespace Webauthn;

use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Exception\InvalidDataException;
use Webauthn\TokenBinding\TokenBinding;
use function array_key_exists;
use function is_array;
use function is_string;
use const JSON_THROW_ON_ERROR;

class CollectedClientData
{
    /**
     * @var mixed[]
     */
    public readonly array $data;

    public readonly string $type;

    public readonly string $challenge;

    public readonly string $origin;

    public readonly null|string $topOrigin;

    public readonly bool $crossOrigin;

    /**
     * @var mixed[]|null
     * @deprecated Since 4.3.0 and will be removed in 5.0.0
     * @infection-ignore-all
     */
    public readonly ?array $tokenBinding;

    /**
     * @param mixed[] $data
     */
    public function __construct(
        public readonly string $rawData,
        array $data
    ) {
        $type = $data['type'] ?? '';
        (is_string($type) && $type !== '') || throw InvalidDataException::create(
            $data,
            'Invalid parameter "type". Shall be a non-empty string.'
        );
        $this->type = $type;

        $challenge = $data['challenge'] ?? '';
        is_string($challenge) || throw InvalidDataException::create(
            $data,
            'Invalid parameter "challenge". Shall be a string.'
        );
        $challenge = Base64UrlSafe::decodeNoPadding($challenge);
        $challenge !== '' || throw InvalidDataException::create(
            $data,
            'Invalid parameter "challenge". Shall not be empty.'
        );
        $this->challenge = $challenge;

        $origin = $data['origin'] ?? '';
        (is_string($origin) && $origin !== '') || throw InvalidDataException::create(
            $data,
            'Invalid parameter "origin". Shall be a non-empty string.'
        );
        $this->origin = $origin;

        $this->topOrigin = $data['topOrigin'] ?? null;
        $this->crossOrigin = $data['crossOrigin'] ?? false;

        $tokenBinding = $data['tokenBinding'] ?? null;
        $tokenBinding === null || is_array($tokenBinding) || throw InvalidDataException::create(
            $data,
            'Invalid parameter "tokenBinding". Shall be an object or .'
        );
        $this->tokenBinding = $tokenBinding;

        $this->data = $data;
    }

    /**
     * @param mixed[] $data
     */
    public static function create(string $rawData, array $data): self
    {
        return new self($rawData, $data);
    }

    public static function createFormJson(string $data): self
    {
        $rawData = Base64UrlSafe::decodeNoPadding($data);
        $json = json_decode($rawData, true, flags: JSON_THROW_ON_ERROR);
        is_array($json) || throw InvalidDataException::create($data, 'Invalid JSON data.');

        return self::create($rawData, $json);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getChallenge(): string
    {
        return $this->challenge;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getCrossOrigin(): bool
    {
        return $this->crossOrigin;
    }

    /**
     * @deprecated Since 4.3.0 and will be removed in 5.0.0
     * @infection-ignore-all
     */
    public function getTokenBinding(): ?TokenBinding
    {
        return $this->tokenBinding === null ? null : TokenBinding::createFormArray($this->tokenBinding);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRawData(): string
    {
        return $this->rawData;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_keys($this->data);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key): mixed
    {
        if (! $this->has($key)) {
            throw InvalidDataException::create($this->data, sprintf('The key "%s" is missing', $key));
        }

        return $this->data[$key];
    }
}
