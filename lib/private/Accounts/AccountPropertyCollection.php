<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Accounts;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;

class AccountPropertyCollection implements IAccountPropertyCollection {
	/** @var IAccountProperty[] */
	protected array $properties = [];

	public function __construct(
		protected string $collectionName,
	) {
	}

	public function setProperties(array $properties): IAccountPropertyCollection {
		/** @var IAccountProperty $property */
		$this->properties = [];
		foreach ($properties as $property) {
			$this->addProperty($property);
		}
		return $this;
	}

	public function getProperties(): array {
		return $this->properties;
	}

	public function addProperty(IAccountProperty $property): IAccountPropertyCollection {
		if ($property->getName() !== $this->collectionName) {
			throw new InvalidArgumentException('Provided property does not match collection name');
		}
		$this->properties[] = $property;
		return $this;
	}

	public function addPropertyWithDefaults(string $value): IAccountPropertyCollection {
		$property = new AccountProperty(
			$this->collectionName,
			$value,
			IAccountManager::SCOPE_LOCAL,
			IAccountManager::NOT_VERIFIED,
			''
		);
		$this->addProperty($property);
		return $this;
	}

	public function removeProperty(IAccountProperty $property): IAccountPropertyCollection {
		$ref = array_search($property, $this->properties, true);
		if ($ref !== false) {
			unset($this->properties[$ref]);
		}
		return $this;
	}

	public function getPropertyByValue(string $value): ?IAccountProperty {
		foreach ($this->properties as $i => $property) {
			if ($property->getValue() === $value) {
				return $property;
			}
		}
		return null;
	}

	public function removePropertyByValue(string $value): IAccountPropertyCollection {
		foreach ($this->properties as $i => $property) {
			if ($property->getValue() === $value) {
				unset($this->properties[$i]);
			}
		}
		return $this;
	}

	public function jsonSerialize(): array {
		return [$this->collectionName => $this->properties];
	}

	public function getName(): string {
		return $this->collectionName;
	}
}
