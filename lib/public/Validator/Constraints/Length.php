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

namespace OCP\Validator\Constraints;

/**
 * Length constrains for strings
 *
 * ```php
 * $name = ...
 * $validator = ...
 * $validator->validate($name, [new Length([
 *     'min' => 2,
 *     'max' => 200,
 *     'minMessage' => "Your first name must be at least {{ limit }} characters long",
 *     'maxMessage' => "Your first name must be at most {{ limit }} characters long",
 * ])]);
 * ```
 */
class Length extends Constraint {
	private ?int $min;
	private ?int $max;
	private ?int $exact;
	private string $minMessage;
	private string $maxMessage;
	private string $exactMessage;

	/**
	 * @psalm-param array{min?: ?int, max?: ?int, exact?: ?int, minMessage?: ?string, maxMessage?: ?string, exactMessage?: ?string} $options
	 * @param array $options An array of options. Either min, max or exact needs to be defined.
	 */
	public function __construct(array $options) {
		parent::__construct();

		$this->min = $options['min'] ?? null;
		$this->max = $options['max'] ?? null;
		$this->exact = $options['exact'] ?? null;

		$this->minMessage = $options['minMessage'] ?? $this->l10n->t('"This value is too short. It should be at least {{ limit }} characters long.');
		$this->maxMessage = $options['maxMessage'] ?? $this->l10n->t('"This value is too long. It should be at most {{ limit }} characters long.');
		$this->exactMessage = $options['exactMessage'] ?? $this->l10n->t('"This value is incorrect. It should be exactly {{ limit }} characters long.');

		assert($this->min !== null || $this->max !== null || $this->exact !== null);
	}

	public function getMin(): ?int {
		return $this->min;
	}

	public function getMax(): ?int {
		return $this->max;
	}

	public function getExact(): ?int {
		return $this->exact;
	}

	public function getMinMessage(): string {
		return $this->minMessage;
	}

	public function getMaxMessage(): string {
		return $this->maxMessage;
	}

	public function getExactMessage(): string {
		return $this->exactMessage;
	}
}
