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
 * The StringConstraint Constraints, validates an string against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class StringConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Verify maxLength
        if (isset($schema->maxLength) && $this->strlen($element) > $schema->maxLength) {
            $this->addError(ConstraintError::LENGTH_MAX(), $path, [
                'maxLength' => $schema->maxLength,
            ]);
        }

        //verify minLength
        if (isset($schema->minLength) && $this->strlen($element) < $schema->minLength) {
            $this->addError(ConstraintError::LENGTH_MIN(), $path, [
                'minLength' => $schema->minLength,
            ]);
        }

        // Verify a regex pattern
        if (isset($schema->pattern) && !preg_match(self::jsonPatternToPhpRegex($schema->pattern), $element)) {
            $this->addError(ConstraintError::PATTERN(), $path, [
                'pattern' => $schema->pattern,
            ]);
        }

        $this->checkFormat($element, $schema, $path, $i);
    }

    private function strlen($string)
    {
        if (extension_loaded('mbstring')) {
            return mb_strlen($string, mb_detect_encoding($string));
        }

        // mbstring is present on all test platforms, so strlen() can be ignored for coverage
        return strlen($string); // @codeCoverageIgnore
    }
}
