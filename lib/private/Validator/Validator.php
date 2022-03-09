<?php

namespace OC\Validator;

use OCP\Validator\IValidator;
use OCP\Validator\Violation;

class Validator implements IValidator {
	public function validate($value, array $constraints): array {
		/** @var Violation[] $violations */
		$violations = [];
		foreach ($constraints as $constraint) {
			$validatorClass = $constraint->validatedBy();
			/** @var IConstraintValidator $validator */
			$validator = new $validatorClass();
			$violations = array_merge($violations, $validator->validate($value, $constraint));
		}
		return $violations;
	}

	public function isValid($value, array $constraints): bool {
		foreach ($constraints as $constraint) {
			$validatorClass = $constraint->validatedBy();
			/** @var IConstraintValidator $validator */
			$validator = new $validatorClass();
			if (count($validator->validate($value, $constraint)) > 0) {
				return false;
			}
		}
		return true;
	}
}
