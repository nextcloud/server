<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Serializer;

use OCP\Serializer\Attribute\SerializationGroups;
use OCP\Serializer\Attribute\SerializationIgnore;
use OCP\Serializer\Attribute\SerializedName;
use OCP\Serializer\Attribute\SerializedPath;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Feeds a Symfony ClassMetadata from the OCP\Serializer\Attribute attributes
 *
 * This mirrors what Symfony's own Symfony\Component\Serializer\Mapping\Loader\AttributeLoader
 * does for its own attributes, but reads OCP\Serializer\Attribute\* instead, so OCP does not
 * have to expose Symfony's attribute classes.
 *
 * Properties are supported, as well as zero-argument `getX()`/`isX()`/`hasX()` accessor methods
 * for virtual (unbacked by a property) attributes. Methods are read first: if an accessor method
 * would resolve to the same attribute name as a property, the method wins outright and the
 * property is ignored entirely for that name, e.g. no accessor/mutator collision resolution or
 * merging like Symfony's own loader is done. Mutator (`setX()`) methods are not supported.
 */
class AttributeLoader implements LoaderInterface {
	#[\Override]
	public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool {
		$reflectionClass = $classMetadata->getReflectionClass();
		$className = $reflectionClass->name;
		$loaded = false;

		$classGroups = [];
		foreach ($reflectionClass->getAttributes(SerializationGroups::class) as $attribute) {
			$classGroups = array_merge($classGroups, $attribute->newInstance()->groups);
		}

		$attributesMetadata = $classMetadata->getAttributesMetadata();

		foreach ($reflectionClass->getMethods() as $method) {
			if ($method->getDeclaringClass()->name !== $className
				|| $method->isStatic()
				|| $method->getNumberOfParameters() > 0
				|| !preg_match('/^(?:get|is|has)([A-Z].*)$/', $method->name, $matches)
			) {
				continue;
			}
			$attributeName = lcfirst($matches[1]);

			$attributeMetadata = new AttributeMetadata($attributeName);
			$hasAttribute = false;
			foreach ($method->getAttributes() as $reflectionAttribute) {
				if ($this->applyAttribute($attributeMetadata, $reflectionAttribute)) {
					$hasAttribute = true;
				}
			}

			if (!$hasAttribute) {
				continue;
			}

			foreach ($classGroups as $group) {
				$attributeMetadata->addGroup($group);
			}

			$attributesMetadata[$attributeName] = $attributeMetadata;
			$classMetadata->addAttributeMetadata($attributeMetadata);
			$loaded = true;
		}

		foreach ($reflectionClass->getProperties() as $property) {
			if ($property->getDeclaringClass()->name !== $className) {
				continue;
			}

			// A method with the same name already claimed this name, see above.
			if (isset($attributesMetadata[$property->name])) {
				continue;
			}

			$attributesMetadata[$property->name] = new AttributeMetadata($property->name);
			$classMetadata->addAttributeMetadata($attributesMetadata[$property->name]);
			$attributeMetadata = $attributesMetadata[$property->name];

			foreach ($classGroups as $group) {
				$attributeMetadata->addGroup($group);
			}

			foreach ($property->getAttributes() as $reflectionAttribute) {
				if ($this->applyAttribute($attributeMetadata, $reflectionAttribute)) {
					$loaded = true;
				}
			}
		}

		return $loaded;
	}

	/**
	 * @return bool true if `$reflectionAttribute` was one of our own attributes
	 */
	private function applyAttribute(AttributeMetadata $attributeMetadata, \ReflectionAttribute $reflectionAttribute): bool {
		$attribute = match ($reflectionAttribute->getName()) {
			SerializationGroups::class, SerializationIgnore::class, SerializedName::class, SerializedPath::class => $reflectionAttribute->newInstance(),
			default => null,
		};

		match (true) {
			$attribute instanceof SerializationGroups => array_map($attributeMetadata->addGroup(...), $attribute->groups),
			$attribute instanceof SerializationIgnore => $attributeMetadata->setIgnore(true),
			$attribute instanceof SerializedName => $attributeMetadata->setSerializedName($attribute->serializedName),
			$attribute instanceof SerializedPath => $attributeMetadata->setSerializedPath($this->parseSerializedPath($attribute)),
			default => null,
		};

		return $attribute !== null;
	}

	private function parseSerializedPath(SerializedPath $attribute): PropertyPath {
		try {
			return new PropertyPath($attribute->serializedPath);
		} catch (InvalidPropertyPathException $e) {
			throw new \InvalidArgumentException(sprintf('"%s" is not a valid serialized path.', $attribute->serializedPath), 0, $e);
		}
	}
}
