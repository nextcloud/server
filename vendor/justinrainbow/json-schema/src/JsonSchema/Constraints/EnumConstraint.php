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
 * The EnumConstraint Constraints, validates an element against a given set of possibilities
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class EnumConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Only validate enum if the attribute exists
        if ($element instanceof UndefinedConstraint && (!isset($schema->required) || !$schema->required)) {
            return;
        }
        $type = gettype($element);

        foreach ($schema->enum as $enum) {
            $enumType = gettype($enum);

            if ($enumType === 'object'
                && $type === 'array'
                && $this->factory->getConfig(self::CHECK_MODE_TYPE_CAST)
                && DeepComparer::isEqual((object) $element, $enum)
            ) {
                return;
            }

            if (($type === $enumType) && DeepComparer::isEqual($element, $enum)) {
                return;
            }

            if (is_numeric($element) && is_numeric($enum) && DeepComparer::isEqual((float) $element, (float) $enum)) {
                return;
            }
        }

        $this->addError(ConstraintError::ENUM(), $path, ['enum' => $schema->enum]);
    }
}
