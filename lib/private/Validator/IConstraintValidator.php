<?php

namespace OC\Validator;

use OCP\Validator\Constraints\Constraint;
use OCP\Validator\Violation;

interface IConstraintValidator {
	/**
	 * @param mixed The value
	 * @return Violation[] An array of violations
	 */
	public function validate($value, Constraint $constraint): array;
}
