<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\OneToOne;

/**
 * @template T
 * @internal
 */
class EntityInfo {
	public readonly string $tableName;

	/** @var array<string, string> */
	public array $mappingColumnToTypes = [];

	/** @var array<string, string> */
	public array $mappingColumnToProperty = [];

	/** @var array<string, string> */
	public array $mappingPropertyToColumn = [];

	/** @var \ReflectionClass<T> */
	public readonly \ReflectionClass $reflection;

	public ?\ReflectionProperty $idProperty = null;

	/**
	 * @var list<PropertyAttributes> $propertiesAttributes
	 */
	public array $propertiesAttributes = [];

	public function __construct(
		public readonly string $entityClass,
	) {
		$this->reflection = new \ReflectionClass($entityClass);

		$entities = $this->reflection->getAttributes(Entity::class, \ReflectionAttribute::IS_INSTANCEOF);
		if (count($entities) !== 1) {
			throw new \InvalidArgumentException('The given entity is missing or has too many of the required #[Entity] attribute');
		}

		$this->tableName = $entities[0]->newInstance()->name;

		foreach ($this->reflection->getProperties() as $property) {
			$attributes = $property->getAttributes();
			$propertyAttributes = new PropertyAttributes($property);

			foreach ($attributes as $attribute) {
				$instance = $attribute->newInstance();
				if ($instance instanceof Column) {
					$propertyAttributes->column = $instance;
					$this->mappingColumnToTypes[$instance->name] = $instance->type;
					$this->mappingColumnToProperty[$instance->name] = $property->getName();
					$this->mappingPropertyToColumn[$property->getName()] = $instance->name;
				} elseif ($instance instanceof Id) {
					$propertyAttributes->id = $instance;
					$this->idProperty = $property;
				} elseif ($instance instanceof OneToOne) {
					$propertyAttributes->oneToOne = $instance;
				} elseif ($instance instanceof JoinColumn) {
					$propertyAttributes->joinColumn = $instance;
				}
			}

			if ($propertyAttributes->id !== null && $propertyAttributes->column === null) {
				throw new \RuntimeException($this->entityClass . ' has a Id attribute on ' . $property->getName() . ' but not the corresponding required Column attribute.');
			}

			$this->propertiesAttributes[] = $propertyAttributes;
		}

		if ($this->idProperty === null) {
			throw new \RuntimeException($this->entityClass . ' does not have a primary key. This is not supported for repositories backed tables.');
		}
	}
}
