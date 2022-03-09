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

class Url extends Constraint {
	/** @var string[] */
	private array $protocols;
	private bool $relativeUrl;
	private string $message;

	/**
	 * @param string|null $message Overwrite the default translated error message
	 *                             to use when the constraint is not fulfilled.
	 */
	public function __construct(bool $relativeUrl = false, array $protocols = ['http', 'https'], ?string $message = null) {
		parent::__construct();
		$this->protocols = $protocols;
		$this->message = $message === null ? $this->l10n->t('"{{ value }}" is not an url') : $message;
		$this->relativeUrl = $relativeUrl;
	}

	public function getProtocols(): array {
		return $this->protocols;
	}

	public function isRelativeUrl(): bool {
		return $this->relativeUrl;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
