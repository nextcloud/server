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
use JsonSchema\Tool\DeepComparer;

/**
 * The CollectionConstraint Constraints, validates an array against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class CollectionConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Verify minItems
        if (isset($schema->minItems) && count($value) < $schema->minItems) {
            $this->addError(ConstraintError::MIN_ITEMS(), $path, ['minItems' => $schema->minItems, 'found' => count($value)]);
        }

        // Verify maxItems
        if (isset($schema->maxItems) && count($value) > $schema->maxItems) {
            $this->addError(ConstraintError::MAX_ITEMS(), $path, ['maxItems' => $schema->maxItems, 'found' => count($value)]);
        }

        // Verify uniqueItems
        if (isset($schema->uniqueItems) && $schema->uniqueItems) {
            $count = count($value);
            for ($x = 0; $x < $count - 1; $x++) {
                for ($y = $x + 1; $y < $count; $y++) {
                    if (DeepComparer::isEqual($value[$x], $value[$y])) {
                        $this->addError(ConstraintError::UNIQUE_ITEMS(), $path);
                        break 2;
                    }
                }
            }
        }

        $this->validateItems($value, $schema, $path, $i);
    }

    /**
     * Validates the items
     *
     * @param array     $value
     * @param \stdClass $schema
     * @param string    $i
     */
    protected function validateItems(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        if (\is_null($schema) || !isset($schema->items)) {
            return;
        }

        if ($schema->items === true) {
            return;
        }

        if (is_object($schema->items)) {
            // just one type definition for the whole array
            foreach ($value as $k => &$v) {
                $initErrors = $this->getErrors();

                // First check if its defined in "items"
                $this->checkUndefined($v, $schema->items, $path, $k);

                // Recheck with "additionalItems" if the first test fails
                if (count($initErrors) < count($this->getErrors()) && (isset($schema->additionalItems) && $schema->additionalItems !== false)) {
                    $secondErrors = $this->getErrors();
                    $this->checkUndefined($v, $schema->additionalItems, $path, $k);
                }

                // Reset errors if needed
                if (isset($secondErrors) && count($secondErrors) < count($this->getErrors())) {
                    $this->errors = $secondErrors;
                } elseif (isset($secondErrors) && count($secondErrors) === count($this->getErrors())) {
                    $this->errors = $initErrors;
                }
            }
            unset($v); /* remove dangling reference to prevent any future bugs
                        * caused by accidentally using $v elsewhere */
        } else {
            // Defined item type definitions
            foreach ($value as $k => &$v) {
                if (array_key_exists($k, $schema->items)) {
                    $this->checkUndefined($v, $schema->items[$k], $path, $k);
                } else {
                    // Additional items
                    if (property_exists($schema, 'additionalItems')) {
                        if ($schema->additionalItems !== false) {
                            $this->checkUndefined($v, $schema->additionalItems, $path, $k);
                        } else {
                            $this->addError(
                                ConstraintError::ADDITIONAL_ITEMS(),
                                $path,
                                [
                                    'item' => $i,
                                    'property' => $k,
                                    'additionalItems' => $schema->additionalItems
                                ]
                            );
                        }
                    } else {
                        // Should be valid against an empty schema
                        $this->checkUndefined($v, new \stdClass(), $path, $k);
                    }
                }
            }
            unset($v); /* remove dangling reference to prevent any future bugs
                        * caused by accidentally using $v elsewhere */

            // Treat when we have more schema definitions than values, not for empty arrays
            if (count($value) > 0) {
                for ($k = count($value); $k < count($schema->items); $k++) {
                    $undefinedInstance = $this->factory->createInstanceFor('undefined');
                    $this->checkUndefined($undefinedInstance, $schema->items[$k], $path, $k);
                }
            }
        }
    }
}
