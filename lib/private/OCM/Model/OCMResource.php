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
	/** @var string[] */
	private array $shareTypes = [];
	/** @var array<string, string> */
	private array $protocols = [];

	public function setName(string $name): static {
		$this->name = $name;

		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setShareTypes(array $shareTypes): static {
		$this->shareTypes = $shareTypes;

		return $this;
	}

	public function getShareTypes(): array {
		return $this->shareTypes;
	}

	public function setProtocols(array $protocols): static {
		$this->protocols = $protocols;

		return $this;
	}

	public function getProtocols(): array {
		return $this->protocols;
	}

	public function import(array $data): static {
		return $this->setName((string)($data['name'] ?? ''))
			->setShareTypes($data['shareTypes'] ?? [])
			->setProtocols($data['protocols'] ?? []);
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'shareTypes' => $this->getShareTypes(),
			'protocols' => $this->getProtocols()
		];
	}
}
