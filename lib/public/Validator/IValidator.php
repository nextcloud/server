<?php

namespace OCP\Validator;

use OCP\Validator\Constraints\Constraint;

interface IValidator {
	/**
	 * Validate a value according to one or more constraints.
	 *
	 * @param mixed $value The value to validate
	 * @param Constraint[] $constraints The validator constraints for the value
	 * @return Violation[] An array of constraints violations. Empty if the value
	 *                     is conforming to every constrains.
	 */
	public function validate($value, array $constraints): array;

	/**
	 * Validate a value according to one or more constraints. This
	 *
	 * @param mixed $value The value to validate
	 * @param Constraint[] $constraints The validator constraints for the value
	 * @return bool Whether the value is valid
	 */
	public function isValid($value, array $constraints): bool;
}
