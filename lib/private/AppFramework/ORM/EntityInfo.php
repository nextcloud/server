<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\ManyToOne;
use OCP\AppFramework\ORM\Attribute\OneToOne;

/**
 * @template T as object
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

	/**
	 * @param class-string<T> $entityClass
	 */
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
				} elseif ($instance instanceof ManyToOne) {
					$propertyAttributes->manyToOne = $instance;
				} elseif ($instance instanceof JoinColumn) {
					$propertyAttributes->joinColumn = $instance;
				}
			}

			if ($propertyAttributes->id !== null && $propertyAttributes->column === null) {
				throw new \RuntimeException($this->entityClass . ' has a Id attribute on ' . $property->getName() . ' but not the corresponding required Column attribute.');
			}

			if ($propertyAttributes->oneToOne !== null
				&& $propertyAttributes->oneToOne->mappedBy !== null
				&& $propertyAttributes->joinColumn !== null
				&& $propertyAttributes->joinColumn->onDelete !== null) {
				throw new \RuntimeException($this->entityClass . '::' . $property->getName() . ' sets JoinColumn::$onDelete on the mappedBy (inverse) side of a OneToOne relation, where it has no effect. Set it on the owning (invertedBy) side instead.');
			}

			if ($propertyAttributes->oneToOne !== null && $propertyAttributes->oneToOne->mappedBy !== null) {
				$this->validateMappedBy($property, $propertyAttributes->oneToOne);
			}

			$this->propertiesAttributes[] = $propertyAttributes;
		}

		if ($this->idProperty === null) {
			throw new \RuntimeException($this->entityClass . ' does not have a primary key. This is not supported for repositories backed tables.');
		}
	}

	/**
	 * Checks that mappedBy actually points at the owning side of the relation, so a typo
	 * fails loudly at construction time instead of silently resolving to null forever.
	 */
	private function validateMappedBy(\ReflectionProperty $property, OneToOne $oneToOne): void {
		$prefix = $this->entityClass . '::' . $property->getName() . ' has mappedBy: \'' . $oneToOne->mappedBy . '\', but ';
		$targetReflection = new \ReflectionClass($oneToOne->targetEntity);

		if (!$targetReflection->hasProperty($oneToOne->mappedBy)) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . ' has no property named \'' . $oneToOne->mappedBy . '\'.');
		}

		$targetProperty = $targetReflection->getProperty($oneToOne->mappedBy);
		$targetOneToOneAttributes = $targetProperty->getAttributes(OneToOne::class, \ReflectionAttribute::IS_INSTANCEOF);
		$targetOneToOne = $targetOneToOneAttributes === [] ? null : $targetOneToOneAttributes[0]->newInstance();

		if ($targetOneToOne === null || $targetOneToOne->invertedBy === null) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $oneToOne->mappedBy . ' is not the owning (invertedBy) side of a OneToOne relation.');
		}

		if ($targetOneToOne->invertedBy !== $property->getName()) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $oneToOne->mappedBy . '\'s invertedBy points at \'' . $targetOneToOne->invertedBy . '\' instead.');
		}

		if ($targetProperty->getAttributes(JoinColumn::class, \ReflectionAttribute::IS_INSTANCEOF) === []) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $oneToOne->mappedBy . ' has no JoinColumn attribute.');
		}
	}
}
