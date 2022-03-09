<?php
/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
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

use OCP\Validator\Constraints\Constraint;
use OCP\Validator\Constraints\Length;
use OCP\Validator\Violation;

class LengthValidator implements IConstraintValidator {
	public function validate($value, Constraint $constraint): array {
		if (!$constraint instanceof Length) {
			throw new \RuntimeException('Invalid constraint');
		}
		if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
			throw new \RuntimeException('The LengthValidator can only validate scalar values or object convertible to string.');
		}

		$stringValue = (string)$value;
		$length = mb_strlen($stringValue);

		if ($constraint->getExact() !== null && $constraint->getExact() !== $length) {
			return [
				(new Violation($constraint->getExactMessage()))
					->addParameter('{{ limit }}', (string)$constraint->getMax())
					->addParameter('{{ value }}', $stringValue)
					->addParameter('{{ stringLength }}', (string)$length),
			];
		}

		if ($constraint->getMin() !== null && $constraint->getMin() > $length) {
			return [
				(new Violation($constraint->getMinMessage()))
					->addParameter('{{ limit }}', (string)$constraint->getMax())
					->addParameter('{{ value }}', $stringValue)
					->addParameter('{{ stringLength }}', (string)$length),
			];
		}
		if ($constraint->getMax() !== null && $constraint->getMax() < $length) {
			return [
				(new Violation($constraint->getMaxMessage()))
					->addParameter('{{ limit }}', (string)$constraint->getMax())
					->addParameter('{{ value }}', $stringValue)
					->addParameter('{{ stringLength }}', (string)$length),
			];
		}

		return [];
	}
}
