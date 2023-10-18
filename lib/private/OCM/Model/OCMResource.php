<?php

declare(strict_types=1);

/**
 * @copyright 2023, Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
 *
 */

namespace OC\OCM\Model;

use OCP\OCM\IOCMResource;

/**
 * @since 28.0.0
 */
class OCMResource implements IOCMResource {
	private string $name = '';
	/** @var string[] */
	private array $shareTypes = [];
	/** @var array<string, string> */
	private array $protocols = [];

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName(string $name): static {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string[] $shareTypes
	 *
	 * @return $this
	 */
	public function setShareTypes(array $shareTypes): static {
		$this->shareTypes = $shareTypes;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getShareTypes(): array {
		return $this->shareTypes;
	}

	/**
	 * @param array<string, string> $protocols
	 *
	 * @return $this
	 */
	public function setProtocols(array $protocols): static {
		$this->protocols = $protocols;

		return $this;
	}

	/**
	 * @return array<string, string>
	 */
	public function getProtocols(): array {
		return $this->protocols;
	}

	/**
	 * import data from an array
	 *
	 * @param array $data
	 *
	 * @return $this
	 * @see self::jsonSerialize()
	 */
	public function import(array $data): static {
		return $this->setName((string)($data['name'] ?? ''))
					->setShareTypes($data['shareTypes'] ?? [])
					->setProtocols($data['protocols'] ?? []);
	}

	/**
	 * @return array{
	 *     name: string,
	 *     shareTypes: string[],
	 *     protocols: array<string, string>
	 * }
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'shareTypes' => $this->getShareTypes(),
			'protocols' => $this->getProtocols()
		];
	}
}
