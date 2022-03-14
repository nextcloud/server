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

namespace OCP\Validator;

interface IValidator {
	/**
	 * Validate a value according to one or more constraints.
	 *
	 * @param mixed $value The value to validate
	 * @param IConstraintValidator[] $constraints The validator constraints for the value
	 * @return Violation[] An array of constraints violations. Empty if the value
	 *                     is conforming to every constrains.
	 */
	public function validate($value, array $constraints): array;

	/**
	 * Validate a value according to one or more constraints. This
	 *
	 * @param mixed $value The value to validate
	 * @param IConstraintValidator[] $constraints The validator constraints for the value
	 * @return bool Whether the value is valid
	 */
	public function isValid($value, array $constraints): bool;
}
