<?php
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
