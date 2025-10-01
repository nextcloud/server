<?php

declare(strict_types=1);

namespace CBOR;

interface Normalizable
{
    /**
     * @return mixed|null
     */
    public function normalize();
}
