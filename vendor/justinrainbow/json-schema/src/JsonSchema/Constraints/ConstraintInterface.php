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
 * The Constraints Interface
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 */
interface ConstraintInterface
{
    /**
     * returns all collected errors
     */
    public function getErrors(): array;

    /**
     * adds errors to this validator
     */
    public function addErrors(array $errors): void;

    /**
     * adds an error
     *
     * @param ConstraintError $constraint the constraint/rule that is broken, e.g.: ConstraintErrors::LENGTH_MIN()
     * @param array           $more       more array elements to add to the error
     */
    public function addError(ConstraintError $constraint, ?JsonPointer $path = null, array $more = []): void;

    /**
     * checks if the validator has not raised errors
     */
    public function isValid(): bool;

    /**
     * invokes the validation of an element
     *
     * @abstract
     *
     * @param mixed $value
     * @param mixed $schema
     * @param mixed $i
     *
     * @throws \JsonSchema\Exception\ExceptionInterface
     */
    public function check(&$value, $schema = null, ?JsonPointer $path = null, $i = null): void;
}
