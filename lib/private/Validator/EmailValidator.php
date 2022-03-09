<?php

namespace OC\Validator;

use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use OCP\Validator\Constraints\Constraint;
use OCP\Validator\Constraints\Email;
use OCP\Validator\Violation;

class EmailValidator implements IConstraintValidator {
	public function validate($value, Constraint $constraint): array {
		if (!$constraint instanceof Email) {
			throw new \RuntimeException('Email validator called with a wrong constraint');
		}

		if ($value === null || '' == $value) {
			return [];
		}

		if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
			throw new \RuntimeException('The EmailValidator can only validate scalar values or object convertible to string.');
		}

		$value = (string) $value;
		if ('' === $value) {
			return [];
		}

		$internalValidator = new EguliasEmailValidator();
		if (!$internalValidator->isValid($value, new NoRFCWarningsValidation())) {
			return [
				(new Violation($constraint->getMessage()))->addParameter('{{ value }}', $value)
			];
		}

		return [];
	}
}
