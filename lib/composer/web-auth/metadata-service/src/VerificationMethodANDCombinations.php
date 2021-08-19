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

class VerificationMethodANDCombinations implements JsonSerializable
{
    /**
     * @var VerificationMethodDescriptor[]
     */
    private $verificationMethods = [];

    public function addVerificationMethodDescriptor(VerificationMethodDescriptor $verificationMethodDescriptor): self
    {
        $this->verificationMethods[] = $verificationMethodDescriptor;

        return $this;
    }

    /**
     * @return VerificationMethodDescriptor[]
     */
    public function getVerificationMethods(): array
    {
        return $this->verificationMethods;
    }

    public static function createFromArray(array $data): self
    {
        $object = new self();

        foreach ($data as $datum) {
            Assertion::isArray($datum, Utils::logicException('Invalid data'));
            $object->addVerificationMethodDescriptor(VerificationMethodDescriptor::createFromArray($datum));
        }

        return $object;
    }

    public function jsonSerialize(): array
    {
        return array_map(static function (VerificationMethodDescriptor $object): array {
            return $object->jsonSerialize();
        }, $this->verificationMethods);
    }
}
