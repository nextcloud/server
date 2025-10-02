<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\StringPrep;

/**
 * Interface for string preparation steps of Internationalized String Preparation algorithm specified by RFC 4518.
 *
 * @see https://tools.ietf.org/html/rfc4518#section-2
 */
interface PrepareStep
{
    /**
     * Apply string preparation step.
     *
     * @param string $string String to prepare
     *
     * @return string Prepared string
     */
    public function apply(string $string): string;
}
