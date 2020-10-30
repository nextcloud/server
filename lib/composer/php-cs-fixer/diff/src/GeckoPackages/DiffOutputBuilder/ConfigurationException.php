<?php

/*
 * This file is part of the GeckoPackages.
 *
 * (c) GeckoPackages https://github.com/GeckoPackages
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Diff\GeckoPackages\DiffOutputBuilder;

use Exception;

final class ConfigurationException extends \InvalidArgumentException
{
    public function __construct(
        $option,
        $expected,
        $value,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct(
            \sprintf(
                'Option "%s" must be %s, got "%s".',
                $option,
                $expected,
                \is_object($value) ? \get_class($value) : (null === $value ? '<null>' : \gettype($value).'#'.$value)
            ),
            $code,
            $previous
        );
    }
}
