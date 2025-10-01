<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\ConstraintError;
use JsonSchema\Entity\JsonPointer;

/**
 * The NumberConstraint Constraints, validates an number against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class NumberConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Verify minimum
        if (isset($schema->exclusiveMinimum)) {
            if (isset($schema->minimum)) {
                if ($schema->exclusiveMinimum && $element <= $schema->minimum) {
                    $this->addError(ConstraintError::EXCLUSIVE_MINIMUM(), $path, ['minimum' => $schema->minimum]);
                } elseif ($element < $schema->minimum) {
                    $this->addError(ConstraintError::MINIMUM(), $path, ['minimum' => $schema->minimum]);
                }
            } else {
                $this->addError(ConstraintError::MISSING_MINIMUM(), $path);
            }
        } elseif (isset($schema->minimum) && $element < $schema->minimum) {
            $this->addError(ConstraintError::MINIMUM(), $path, ['minimum' => $schema->minimum]);
        }

        // Verify maximum
        if (isset($schema->exclusiveMaximum)) {
            if (isset($schema->maximum)) {
                if ($schema->exclusiveMaximum && $element >= $schema->maximum) {
                    $this->addError(ConstraintError::EXCLUSIVE_MAXIMUM(), $path, ['maximum' => $schema->maximum]);
                } elseif ($element > $schema->maximum) {
                    $this->addError(ConstraintError::MAXIMUM(), $path, ['maximum' => $schema->maximum]);
                }
            } else {
                $this->addError(ConstraintError::MISSING_MAXIMUM(), $path);
            }
        } elseif (isset($schema->maximum) && $element > $schema->maximum) {
            $this->addError(ConstraintError::MAXIMUM(), $path, ['maximum' => $schema->maximum]);
        }

        // Verify divisibleBy - Draft v3
        if (isset($schema->divisibleBy) && $this->fmod($element, $schema->divisibleBy) != 0) {
            $this->addError(ConstraintError::DIVISIBLE_BY(), $path, ['divisibleBy' => $schema->divisibleBy]);
        }

        // Verify multipleOf - Draft v4
        if (isset($schema->multipleOf) && $this->fmod($element, $schema->multipleOf) != 0) {
            $this->addError(ConstraintError::MULTIPLE_OF(), $path, ['multipleOf' => $schema->multipleOf]);
        }

        $this->checkFormat($element, $schema, $path, $i);
    }

    private function fmod($number1, $number2)
    {
        $modulus = ($number1 - round($number1 / $number2) * $number2);
        $precision = 0.0000000001;

        if (-$precision < $modulus && $modulus < $precision) {
            return 0.0;
        }

        return $modulus;
    }
}
