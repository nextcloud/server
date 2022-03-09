<?php

namespace OC\Validator;

use OCP\Validator\Constraints\Constraint;
use OCP\Validator\Constraints\NotBlank;
use OCP\Validator\Violation;

class NotBlankValidator implements IConstraintValidator {
	public function validate($value, Constraint $constraint): array {
		if (!$constraint instanceof NotBlank) {
			throw new \RuntimeException();
		}

		if ($constraint->allowNull() && null === $value) {
			return [];
		}

		if (false === $value || (empty($value) && '0' != $value)) {
			return [
				(new Violation($constraint->getMessage()))
			];
		}
		return [];
	}
}
