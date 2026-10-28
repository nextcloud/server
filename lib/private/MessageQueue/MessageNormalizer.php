<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\MessageQueue\Exception\InvalidMessageException;

/**
 * Converts messages from and to arrays of plain values, based on their
 * constructor signature. Class names are never read from the payload.
 */
final readonly class MessageNormalizer {
	private const string DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

	public function __construct(
		private MessageMetadataReader $metadataReader,
	) {
	}

	/**
	 * @return array<string, mixed>
	 * @throws InvalidMessageException
	 */
	public function normalize(object $message): array {
		$class = $message::class;
		$data = [];
		foreach ($this->getConstructorParameters($class) as $parameter) {
			$name = $parameter->getName();
			if (!property_exists($message, $name) || !(new \ReflectionProperty($message, $name))->isPublic()) {
				throw new InvalidMessageException('Constructor parameter $' . $name . ' of ' . $class . ' must be a public promoted property');
			}

			$data[$name] = $this->normalizeValue($message->$name, $class . '::$' . $name);
		}

		return $data;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @param array<array-key, mixed> $data
	 * @return T
	 * @throws InvalidMessageException
	 */
	public function denormalize(string $class, array $data): object {
		$arguments = [];
		foreach ($this->getConstructorParameters($class) as $parameter) {
			$name = $parameter->getName();
			if (!array_key_exists($name, $data)) {
				if ($parameter->isDefaultValueAvailable()) {
					continue;
				}

				throw new InvalidMessageException('Missing value for ' . $class . '::$' . $name);
			}

			$arguments[$name] = $this->denormalizeValue($data[$name], $parameter->getType(), $class . '::$' . $name);
		}

		return (new \ReflectionClass($class))->newInstanceArgs($arguments);
	}

	/**
	 * @param class-string $class
	 * @return list<\ReflectionParameter>
	 */
	private function getConstructorParameters(string $class): array {
		return (new \ReflectionClass($class))->getConstructor()?->getParameters() ?? [];
	}

	private function normalizeValue(mixed $value, string $path, bool $inArray = false): int|float|string|bool|array|null {
		if ($value === null || is_scalar($value)) {
			return $value;
		}

		if (is_array($value)) {
			return array_map(fn (mixed $item): int|float|string|bool|array|null => $this->normalizeValue($item, $path, true), $value);
		}

		if (!$inArray && is_object($value)) {
			if ($value instanceof \BackedEnum) {
				return $value->value;
			}

			if ($value instanceof \DateTimeImmutable) {
				return $value->format(self::DATE_FORMAT);
			}

			if ($this->metadataReader->isMessage($value::class)) {
				return $this->normalize($value);
			}
		}

		throw new InvalidMessageException('Unsupported value of type ' . get_debug_type($value) . ' in ' . $path);
	}

	private function denormalizeValue(mixed $value, ?\ReflectionType $type, string $path): int|float|string|bool|array|object|null {
		if (!$type instanceof \ReflectionNamedType) {
			throw new InvalidMessageException('A single named type is required in ' . $path);
		}

		if ($value === null) {
			if ($type->allowsNull()) {
				return null;
			}

			throw new InvalidMessageException('Unexpected null value in ' . $path);
		}

		$typeName = $type->getName();
		if ($type->isBuiltin()) {
			return match (true) {
				$typeName === 'int' && is_int($value) => $value,
				$typeName === 'float' && (is_float($value) || is_int($value)) => (float)$value,
				$typeName === 'string' && is_string($value) => $value,
				$typeName === 'bool' && is_bool($value) => $value,
				$typeName === 'array' && is_array($value) => $value,
				default => throw new InvalidMessageException('Expected ' . $typeName . ' but got ' . get_debug_type($value) . ' in ' . $path),
			};
		}

		if (is_subclass_of($typeName, \BackedEnum::class) && (is_int($value) || is_string($value))) {
			return $typeName::tryFrom($value) ?? throw new InvalidMessageException('Invalid enum value in ' . $path);
		}

		if (in_array($typeName, [\DateTimeImmutable::class, \DateTimeInterface::class], true) && is_string($value)) {
			return \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $value)
				?: throw new InvalidMessageException('Invalid date in ' . $path);
		}

		if (is_array($value) && $this->metadataReader->isMessage($typeName)) {
			return $this->denormalize($typeName, $value);
		}

		throw new InvalidMessageException('Unsupported type ' . $typeName . ' in ' . $path);
	}
}
