<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
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
