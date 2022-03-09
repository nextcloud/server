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

/**
 * This object represents a constraint violation when validating a value.
 */
class Violation {
	private string $message;
	private array $parameters;

	public function __construct(string $message) {
		$this->message = $message;
		$this->parameters = [];
	}

	/**
	 * Returns the violation message. This can be directly displayed to the
	 * user, if wanted.
	 */
	public function getMessage(): string {
		$message = $this->message;
		foreach ($this->parameters as $value => $representation) {
			$message = str_replace($representation, $value, $message);
		}
		return $message;
	}

	/**
	 * Inject a parameter inside the violation message.
	 *
	 * This allows to inject dynamic information in the violation message.
	 *
	 * ```php
	 * $violation = new Violation('This value should be less than {{ max }}.');
	 * $violation->addParameter('{{ max }}', 100);
	 * assert($violation->getMessage() === 'This value should be less than 100.')
	 * ```
	 */
	public function addParameter(string $representation, string $value): self {
		$this->parameters[] = [
			$representation => $value,
		];
		return $this;
	}
}
