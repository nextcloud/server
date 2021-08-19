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

namespace Webauthn;

use function array_key_exists;
use Assert\Assertion;
use Base64Url\Base64Url;
use InvalidArgumentException;
use function Safe\json_decode;
use function Safe\sprintf;
use Webauthn\TokenBinding\TokenBinding;

class CollectedClientData
{
    /**
     * @var string
     */
    private $rawData;

    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $challenge;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var mixed[]|null
     */
    private $tokenBinding;

    /**
     * @param mixed[] $data
     */
    public function __construct(string $rawData, array $data)
    {
        $this->type = $this->findData($data, 'type');
        $this->challenge = $this->findData($data, 'challenge', true, true);
        $this->origin = $this->findData($data, 'origin');
        $this->tokenBinding = $this->findData($data, 'tokenBinding', false);
        $this->rawData = $rawData;
        $this->data = $data;
    }

    public static function createFormJson(string $data): self
    {
        $rawData = Base64Url::decode($data);
        $json = json_decode($rawData, true);
        Assertion::isArray($json, 'Invalid collected client data');

        return new self($rawData, $json);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getChallenge(): string
    {
        return $this->challenge;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getTokenBinding(): ?TokenBinding
    {
        return null === $this->tokenBinding ? null : TokenBinding::createFormArray($this->tokenBinding);
    }

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

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(sprintf('The key "%s" is missing', $key));
        }

        return $this->data[$key];
    }

    /**
     * @param mixed[] $json
     *
     * @return mixed|null
     */
    private function findData(array $json, string $key, bool $isRequired = true, bool $isB64 = false)
    {
        if (!array_key_exists($key, $json)) {
            if ($isRequired) {
                throw new InvalidArgumentException(sprintf('The key "%s" is missing', $key));
            }

            return;
        }

        return $isB64 ? Base64Url::decode($json[$key]) : $json[$key];
    }
}
