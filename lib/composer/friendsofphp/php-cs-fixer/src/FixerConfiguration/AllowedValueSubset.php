<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\FixerConfiguration;

/**
 * @internal
 */
final class AllowedValueSubset
{
    private $allowedValues;

    public function __construct(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;
    }

    /**
     * Checks whether the given values are a subset of the allowed ones.
     *
     * @param mixed $values the value to validate
     *
     * @return bool
     */
    public function __invoke($values)
    {
        if (!\is_array($values)) {
            return false;
        }

        foreach ($values as $value) {
            if (!\in_array($value, $this->allowedValues, true)) {
                return false;
            }
        }

        return true;
    }

    public function getAllowedValues()
    {
        return $this->allowedValues;
    }
}
