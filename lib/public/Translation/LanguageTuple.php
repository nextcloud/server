<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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
 */


namespace OCP\Translation;

use JsonSerializable;

/**
 * @since 26.0.0
 */
class LanguageTuple implements JsonSerializable {
	/**
	 * @since 26.0.0
	 */
	public function __construct(
		private string $from,
		private string $fromLabel,
		private string $to,
		private string $toLabel
	) {
	}

	/**
	 * @since 26.0.0
	 * @return array{from: string, fromLabel: string, to: string, toLabel: string}
	 */
	public function jsonSerialize(): array {
		return [
			'from' => $this->from,
			'fromLabel' => $this->fromLabel,
			'to' => $this->to,
			'toLabel' => $this->toLabel,
		];
	}

	/**
	 * @since 26.0.0
	 */
	public static function fromArray(array $data): LanguageTuple {
		return new self(
			$data['from'],
			$data['fromLabel'],
			$data['to'],
			$data['toLabel'],
		);
	}
}
