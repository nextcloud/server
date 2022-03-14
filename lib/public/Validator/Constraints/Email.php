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

namespace OCP\Validator\Constraints;

use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\EmailValidator as EguliasEmailValidator;
use OCP\Validator\Violation;

class Email extends Constraint {
	private string $message;
	/**
	 * @param string|null $message Overwrite the default translated error message
	 *                             to use when the constraint is not fulfilled.
	 */
	public function __construct(?string $message = null) {
		parent::__construct();
		$this->message = $message === null ? $this->l10n->t('"{{ value }}" is not a valid email address') : $message;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function validate($value): array {
		if ($value === null || $value == '') {
			return [];
		}

		if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
			throw new \RuntimeException('The EmailValidator can only validate scalar values or object convertible to string.');
		}

		$value = (string) $value;
		if ($value === '') {
			return [];
		}

		$internalValidator = new EguliasEmailValidator();
		if (!$internalValidator->isValid($value, new NoRFCWarningsValidation())) {
			return [
				(new Violation($this->getMessage()))->addParameter('{{ value }}', $value)
			];
		}

		return [];
	}
}
