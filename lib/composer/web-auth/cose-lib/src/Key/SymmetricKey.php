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

namespace Cose\Key;

use Assert\Assertion;

class SymmetricKey extends Key
{
    public const DATA_K = -1;

    public function __construct(array $data)
    {
        parent::__construct($data);
        Assertion::eq($data[self::TYPE], self::TYPE_OCT, 'Invalid symmetric key. The key type does not correspond to a symmetric key');
        Assertion::keyExists($data, self::DATA_K, 'Invalid symmetric key. The parameter "k" is missing');
    }

    public function k(): string
    {
        return $this->get(self::DATA_K);
    }
}
