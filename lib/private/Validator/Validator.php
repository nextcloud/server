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

use OCP\Validator\IValidator;
use OCP\Validator\Violation;

class Validator implements IValidator {
	public function validate($value, array $constraints): array {
		/** @var Violation[] $violations */
		$violations = [];
		foreach ($constraints as $constraint) {
			$violations = array_merge($violations, $constraint->validate($value));
		}
		return $violations;
	}

	public function isValid($value, array $constraints): bool {
		foreach ($constraints as $constraint) {
			if (count($constraint->validate($value)) > 0) {
				return false;
			}
		}
		return true;
	}
}
