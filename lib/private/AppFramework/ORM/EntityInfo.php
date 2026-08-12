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
use OCP\DB\Schema\ColumnType;

/**
 * @template T as object
 */
final class EntityInfo {
	public readonly string $tableName;

	/** @var array<string, ColumnType> */
	public array $mappingColumnToTypes = [];

	/** @var array<string, string> */
	public array $mappingColumnToProperty = [];

	/** @var array<string, string> */
	public array $mappingPropertyToColumn = [];

	/** @var \ReflectionClass<T> */
	public readonly \ReflectionClass $reflection;

	/**
	 * All properties carrying an #[Id] attribute, in declaration order. More than one entry
	 * means the entity has a composite primary key.
	 *
	 * @var list<\ReflectionProperty>
	 */
	public array $idProperties = [];

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
					$this->idProperties[] = $property;
				} elseif ($instance instanceof OneToOne) {
					$propertyAttributes->oneToOne = $instance;
				} elseif ($instance instanceof ManyToOne) {
					$propertyAttributes->manyToOne = $instance;
				} elseif ($instance instanceof JoinColumn) {
					$propertyAttributes->joinColumn = $instance;
				}
			}

			if ($propertyAttributes->id instanceof Id && !$propertyAttributes->column instanceof Column) {
				throw new \RuntimeException($this->entityClass . ' has an Id attribute on ' . $property->getName() . ' but not the corresponding required Column attribute.');
			}

			if ($propertyAttributes->oneToOne instanceof OneToOne
				&& $propertyAttributes->oneToOne->mappedBy !== null
				&& $propertyAttributes->joinColumn instanceof JoinColumn
				&& $propertyAttributes->joinColumn->onDelete !== null) {
				throw new \RuntimeException($this->entityClass . '::' . $property->getName() . ' sets JoinColumn::$onDelete on the mappedBy (inverse) side of a OneToOne relation, where it has no effect. Set it on the owning (invertedBy) side instead.');
			}

			if ($propertyAttributes->oneToOne instanceof OneToOne && $propertyAttributes->oneToOne->mappedBy !== null) {
				$this->validateMappedBy($property, $propertyAttributes->oneToOne, $propertyAttributes->oneToOne->mappedBy);
			}

			$this->propertiesAttributes[] = $propertyAttributes;
		}

		if ($this->idProperties === []) {
			throw new \RuntimeException($this->entityClass . ' does not have a primary key. This is not supported for repositories backed tables.');
		}
	}

	/**
	 * @return non-empty-list<\ReflectionProperty>
	 */
	public function getIdProperties(): array {
		if ($this->idProperties === []) {
			throw new \LogicException('Unreachable: the constructor already guarantees idProperties is not empty.');
		}

		return $this->idProperties;
	}

	public function hasCompositeIdProperty(): bool {
		return count($this->idProperties) > 1;
	}

	/**
	 * Convenience accessor for code paths (e.g. relation joins) that only support entities with
	 * a single-column primary key.
	 */
	public function getSingleIdProperty(): \ReflectionProperty {
		$idProperties = $this->getIdProperties();
		if (count($idProperties) > 1) {
			throw new \LogicException($this->entityClass . ' has a composite primary key, which is not supported here.');
		}

		return $idProperties[0];
	}

	/**
	 * Checks that mappedBy actually points at the owning side of the relation, so a typo
	 * fails loudly at construction time instead of silently resolving to null forever.
	 *
	 * @param non-empty-string $mappedBy Same value as $oneToOne->mappedBy, narrowed non-null by the caller.
	 */
	private function validateMappedBy(\ReflectionProperty $property, OneToOne $oneToOne, string $mappedBy): void {
		$prefix = $this->entityClass . '::' . $property->getName() . " has mappedBy: '" . $mappedBy . "', but ";
		$targetReflection = new \ReflectionClass($oneToOne->targetEntity);

		if (!$targetReflection->hasProperty($mappedBy)) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . " has no property named '" . $mappedBy . "'.");
		}

		$targetProperty = $targetReflection->getProperty($mappedBy);
		$targetOneToOneAttributes = $targetProperty->getAttributes(OneToOne::class, \ReflectionAttribute::IS_INSTANCEOF);
		$targetOneToOne = $targetOneToOneAttributes === [] ? null : $targetOneToOneAttributes[0]->newInstance();

		if ($targetOneToOne === null || $targetOneToOne->invertedBy === null) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $mappedBy . ' is not the owning (invertedBy) side of a OneToOne relation.');
		}

		if ($targetOneToOne->invertedBy !== $property->getName()) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $mappedBy . "'s invertedBy points at '" . $targetOneToOne->invertedBy . "' instead.");
		}

		if ($targetProperty->getAttributes(JoinColumn::class, \ReflectionAttribute::IS_INSTANCEOF) === []) {
			throw new \RuntimeException($prefix . $oneToOne->targetEntity . '::' . $mappedBy . ' has no JoinColumn attribute.');
		}
	}
}
