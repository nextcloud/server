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

class RogueListEntry implements JsonSerializable
{
    /**
     * @var string
     */
    private $sk;

    /**
     * @var string
     */
    private $date;

    public function __construct(string $sk, string $date)
    {
        $this->sk = $sk;
        $this->date = $date;
    }

    public function getSk(): string
    {
        return $this->sk;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public static function createFromArray(array $data): self
    {
        Assertion::keyExists($data, 'sk', 'The key "sk" is missing');
        Assertion::string($data['sk'], 'The key "sk" is invalid');
        Assertion::keyExists($data, 'date', 'The key "date" is missing');
        Assertion::string($data['date'], 'The key "date" is invalid');

        return new self(
            $data['sk'],
            $data['date']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sk' => $this->sk,
            'date' => $this->date,
        ];
    }
}
