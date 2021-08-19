<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

class MapItem
{
    /**
     * @var CBORObject
     */
    private $key;

    /**
     * @var CBORObject
     */
    private $value;

    public function __construct(CBORObject $key, CBORObject $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): CBORObject
    {
        return $this->key;
    }

    public function getValue(): CBORObject
    {
        return $this->value;
    }
}
