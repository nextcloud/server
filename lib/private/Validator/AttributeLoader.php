<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Validator;

use OCP\Validator\Constraints\Choice;
use OCP\Validator\Constraints\Count;
use OCP\Validator\Constraints\Email;
use OCP\Validator\Constraints\Length;
use OCP\Validator\Constraints\NotBlank;
use OCP\Validator\Constraints\NotNull;
use OCP\Validator\Constraints\Range;
use OCP\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

/**
 * Feeds a Symfony ClassMetadata from the OCP\Validator\Constraints attributes
 *
 * This mirrors what Symfony's own Symfony\Component\Validator\Mapping\Loader\AttributeLoader
 * does for its own attributes, but reads OCP\Validator\Constraints\* instead, translating each
 * one into the equivalent Symfony constraint, so OCP does not have to expose Symfony's
 * constraint classes.
 *
 * Only properties are supported, accessor methods are not.
 */
class AttributeLoader implements LoaderInterface {
	#[\Override]
	public function loadClassMetadata(ClassMetadata $metadata): bool {
		$reflectionClass = $metadata->getReflectionClass();
		$className = $reflectionClass->name;
		$loaded = false;

		foreach ($reflectionClass->getProperties() as $property) {
			if ($property->getDeclaringClass()->name !== $className) {
				continue;
			}

			foreach ($property->getAttributes() as $reflectionAttribute) {
				$constraint = $this->buildConstraint($reflectionAttribute);
				if ($constraint === null) {
					continue;
				}

				$metadata->addPropertyConstraint($property->name, $constraint);
				$loaded = true;
			}
		}

		return $loaded;
	}

	private function buildConstraint(\ReflectionAttribute $reflectionAttribute): ?SymfonyConstraint {
		$attribute = match ($reflectionAttribute->getName()) {
			NotBlank::class, NotNull::class, Length::class, Email::class, Range::class, Choice::class, Regex::class, Count::class => $reflectionAttribute->newInstance(),
			default => null,
		};

		return match (true) {
			$attribute instanceof NotBlank => new Assert\NotBlank(
				message: $attribute->message,
				allowNull: $attribute->allowNull,
				groups: $attribute->groups,
			),
			$attribute instanceof NotNull => new Assert\NotNull(
				message: $attribute->message,
				groups: $attribute->groups,
			),
			$attribute instanceof Length => new Assert\Length(
				exactly: $attribute->exactly,
				min: $attribute->min,
				max: $attribute->max,
				exactMessage: $attribute->exactMessage,
				minMessage: $attribute->minMessage,
				maxMessage: $attribute->maxMessage,
				groups: $attribute->groups,
			),
			$attribute instanceof Email => new Assert\Email(
				message: $attribute->message,
				groups: $attribute->groups,
			),
			$attribute instanceof Range => new Assert\Range(
				min: $attribute->min,
				max: $attribute->max,
				notInRangeMessage: $attribute->notInRangeMessage,
				minMessage: $attribute->minMessage,
				maxMessage: $attribute->maxMessage,
				groups: $attribute->groups,
			),
			$attribute instanceof Choice => new Assert\Choice(
				choices: $attribute->choices,
				multiple: $attribute->multiple,
				min: $attribute->min,
				max: $attribute->max,
				message: $attribute->message,
				multipleMessage: $attribute->multipleMessage,
				minMessage: $attribute->minMessage,
				maxMessage: $attribute->maxMessage,
				groups: $attribute->groups,
			),
			$attribute instanceof Regex => new Assert\Regex(
				pattern: $attribute->pattern,
				match: $attribute->match,
				message: $attribute->message,
				groups: $attribute->groups,
			),
			$attribute instanceof Count => new Assert\Count(
				exactly: $attribute->exactly,
				min: $attribute->min,
				max: $attribute->max,
				exactMessage: $attribute->exactMessage,
				minMessage: $attribute->minMessage,
				maxMessage: $attribute->maxMessage,
				groups: $attribute->groups,
			),
			default => null,
		};
	}
}
