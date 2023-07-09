<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */
namespace OC\Accounts;

use Generator;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IUser;
use RuntimeException;

class Account implements IAccount {
	use TAccountsHelper;

	/** @var IAccountPropertyCollection[]|IAccountProperty[] */
	private array $properties = [];

	public function __construct(
		private IUser $user,
	) {
	}

	public function setProperty(string $property, string $value, string $scope, string $verified, string $verificationData = ''): IAccount {
		if ($this->isCollection($property)) {
			throw new \InvalidArgumentException('setProperty cannot set an IAccountsPropertyCollection');
		}
		$this->properties[$property] = new AccountProperty($property, $value, $scope, $verified, $verificationData);
		return $this;
	}

	public function getProperty(string $property): IAccountProperty {
		if ($this->isCollection($property)) {
			throw new \InvalidArgumentException('getProperty cannot retrieve an IAccountsPropertyCollection');
		}
		if (!array_key_exists($property, $this->properties) || !$this->properties[$property] instanceof IAccountProperty) {
			throw new PropertyDoesNotExistException($property);
		}
		return $this->properties[$property];
	}

	public function getProperties(): array {
		return array_filter($this->properties, function ($obj) {
			return $obj instanceof IAccountProperty;
		});
	}

	public function setAllPropertiesFromJson(array $properties): IAccount {
		foreach ($properties as $propertyName => $propertyObject) {
			if ($this->isCollection($propertyName)) {
				$collection = new AccountPropertyCollection($propertyName);
				/** @var array<int, IAccountProperty> $collectionProperties */
				$collectionProperties = [];
				/** @var array<int, array<string, string>> $propertyObject */
				foreach ($propertyObject as ['value' => $value, 'scope' => $scope, 'verified' => $verified, 'verificationData' => $verificationData]) {
					$collectionProperties[] = new AccountProperty($collection->getName(), $value, $scope, $verified, $verificationData);
				}
				$collection->setProperties($collectionProperties);
				$this->setPropertyCollection($collection);
			} else {
				/** @var array<string, string> $propertyObject */
				['value' => $value, 'scope' => $scope, 'verified' => $verified, 'verificationData' => $verificationData] = $propertyObject;
				$this->setProperty($propertyName, $value, $scope, $verified, $verificationData);
			}
		}

		return $this;
	}

	public function getAllProperties(): Generator {
		foreach ($this->properties as $propertyObject) {
			if ($propertyObject instanceof IAccountProperty) {
				yield $propertyObject;
			} elseif ($propertyObject instanceof IAccountPropertyCollection) {
				foreach ($propertyObject->getProperties() as $property) {
					yield $property;
				}
			}
		}
	}

	public function getFilteredProperties(string $scope = null, string $verified = null): array {
		$result = $incrementals = [];
		/** @var IAccountProperty $obj */
		foreach ($this->getAllProperties() as $obj) {
			if ($scope !== null && $scope !== $obj->getScope()) {
				continue;
			}
			if ($verified !== null && $verified !== $obj->getVerified()) {
				continue;
			}
			$index = $obj->getName();
			if ($this->isCollection($index)) {
				$incrementals[$index] = ($incrementals[$index] ?? -1) + 1;
				$index .= '#' . $incrementals[$index];
			}
			$result[$index] = $obj;
		}
		return $result;
	}

	/** @return array<string, IAccountProperty|array<int, IAccountProperty>> */
	public function jsonSerialize(): array {
		$properties = $this->properties;
		foreach ($properties as $propertyName => $propertyObject) {
			if ($propertyObject instanceof IAccountPropertyCollection) {
				// Override collection serialization to discard duplicate name
				$properties[$propertyName] = $propertyObject->jsonSerialize()[$propertyName];
			}
		}
		return $properties;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function setPropertyCollection(IAccountPropertyCollection $propertyCollection): IAccount {
		$this->properties[$propertyCollection->getName()] = $propertyCollection;
		return $this;
	}

	public function getPropertyCollection(string $propertyCollectionName): IAccountPropertyCollection {
		if (!$this->isCollection($propertyCollectionName)) {
			throw new PropertyDoesNotExistException($propertyCollectionName);
		}
		if (!array_key_exists($propertyCollectionName, $this->properties)) {
			$this->properties[$propertyCollectionName] = new AccountPropertyCollection($propertyCollectionName);
		}
		if (!$this->properties[$propertyCollectionName] instanceof IAccountPropertyCollection) {
			throw new RuntimeException('Requested collection is not an IAccountPropertyCollection');
		}
		return $this->properties[$propertyCollectionName];
	}
}
