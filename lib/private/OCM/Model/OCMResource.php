<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM\Model;

use OCP\OCM\IOCMResource;

/**
 * @since 28.0.0
 */
class OCMResource implements IOCMResource {
	private string $name = '';
	/** @var list<string> */
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
	 * @param list<string> $shareTypes
	 *
	 * @return $this
	 */
	public function setShareTypes(array $shareTypes): static {
		$this->shareTypes = $shareTypes;

		return $this;
	}

	/**
	 * @return list<string>
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
	 *     shareTypes: list<string>,
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
