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
 * The ConstConstraint Constraints, validates an element against a constant value
 *
 * @author Martin Helmich <martin@helmich.me>
 */
class ConstConstraint extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$element, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        // Only validate const if the attribute exists
        if ($element instanceof UndefinedConstraint && (!isset($schema->required) || !$schema->required)) {
            return;
        }
        $const = $schema->const;

        $type = gettype($element);
        $constType = gettype($const);

        if ($this->factory->getConfig(self::CHECK_MODE_TYPE_CAST) && $type === 'array' && $constType === 'object') {
            if (DeepComparer::isEqual((object) $element, $const)) {
                return;
            }
        }

        if (DeepComparer::isEqual($element, $const)) {
            return;
        }

        $this->addError(ConstraintError::CONSTANT(), $path, ['const' => $schema->const]);
    }
}
