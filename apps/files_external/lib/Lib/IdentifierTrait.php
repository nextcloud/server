<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

/**
 * Trait for objects requiring an identifier (and/or identifier aliases)
 * Also supports deprecation to a different object, linking the objects
 */
trait IdentifierTrait {

	protected string $identifier = '';

	/** @var string[] */
	protected array $identifierAliases = [];
	protected ?IIdentifier $deprecateTo = null;

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function setIdentifier(string $identifier): self {
		$this->identifier = $identifier;
		$this->identifierAliases[] = $identifier;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getIdentifierAliases(): array {
		return $this->identifierAliases;
	}

	public function addIdentifierAlias(string $alias): self {
		$this->identifierAliases[] = $alias;
		return $this;
	}

	public function getDeprecateTo(): ?IIdentifier {
		return $this->deprecateTo;
	}

	public function deprecateTo(IIdentifier $destinationObject): self {
		$this->deprecateTo = $destinationObject;
		return $this;
	}

	public function jsonSerializeIdentifier(): array {
		$data = [
			'identifier' => $this->identifier,
			'identifierAliases' => $this->identifierAliases,
		];
		if ($this->deprecateTo) {
			$data['deprecateTo'] = $this->deprecateTo->getIdentifier();
		}
		return $data;
	}
}
