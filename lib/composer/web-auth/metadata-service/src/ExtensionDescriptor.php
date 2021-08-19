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

use function array_key_exists;
use Assert\Assertion;
use JsonSerializable;

class ExtensionDescriptor implements JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int|null
     */
    private $tag;

    /**
     * @var string|null
     */
    private $data;

    /**
     * @var bool
     */
    private $fail_if_unknown;

    public function __construct(string $id, ?int $tag, ?string $data, bool $fail_if_unknown)
    {
        if (null !== $tag) {
            Assertion::greaterOrEqualThan($tag, 0, Utils::logicException('Invalid data. The parameter "tag" shall be a positive integer'));
        }
        $this->id = $id;
        $this->tag = $tag;
        $this->data = $data;
        $this->fail_if_unknown = $fail_if_unknown;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTag(): ?int
    {
        return $this->tag;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function isFailIfUnknown(): bool
    {
        return $this->fail_if_unknown;
    }

    public static function createFromArray(array $data): self
    {
        $data = Utils::filterNullValues($data);
        Assertion::keyExists($data, 'id', Utils::logicException('Invalid data. The parameter "id" is missing'));
        Assertion::string($data['id'], Utils::logicException('Invalid data. The parameter "id" shall be a string'));
        Assertion::keyExists($data, 'fail_if_unknown', Utils::logicException('Invalid data. The parameter "fail_if_unknown" is missing'));
        Assertion::boolean($data['fail_if_unknown'], Utils::logicException('Invalid data. The parameter "fail_if_unknown" shall be a boolean'));
        if (array_key_exists('tag', $data)) {
            Assertion::integer($data['tag'], Utils::logicException('Invalid data. The parameter "tag" shall be a positive integer'));
        }
        if (array_key_exists('data', $data)) {
            Assertion::string($data['data'], Utils::logicException('Invalid data. The parameter "data" shall be a string'));
        }

        return new self(
            $data['id'],
            $data['tag'] ?? null,
            $data['data'] ?? null,
            $data['fail_if_unknown']
        );
    }

    public function jsonSerialize(): array
    {
        $result = [
            'id' => $this->id,
            'tag' => $this->tag,
            'data' => $this->data,
            'fail_if_unknown' => $this->fail_if_unknown,
        ];

        return Utils::filterNullValues($result);
    }
}
