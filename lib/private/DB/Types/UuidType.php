<?php

/*
 * This file is copied and adapted from Symfony Doctrine Bridge
 * SPDX-FileCopyrightText: Gennadi McKelvey
 * SPDX-FileCopyrightText: Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OC\DB\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use OCP\Server;
use OCP\Uuid\IUuid;
use OCP\Uuid\IUuidBuilder;

/**
 * Special doctrine type implementation to store a UUID.
 *
 * It uses the native UUID type from the database if supported or a binary(16).
 */
class UuidType extends Type {
	public function getName(): string {
		return 'uuid';
	}

	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
		if ($this->hasNativeGuidType($platform)) {
			return $platform->getGuidTypeDeclarationSQL($column);
		}

		return $platform->getBinaryTypeDeclarationSQL([
			'length' => 16,
			'fixed' => true,
		]);
	}

	/**
	 * @throws ConversionException
	 */
	public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IUuid {
		if ($value instanceof IUuid || null === $value) {
			return $value;
		}

		if (!\is_string($value)) {
			$this->throwInvalidType($value);
		}

		try {
			return Server::get(IUuidBuilder::class)->fromString($value);
		} catch (\InvalidArgumentException $e) {
			$this->throwValueNotConvertible($value, $e);
		}
	}

	/**
	 * @throws ConversionException
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string {
		$toString = $this->hasNativeGuidType($platform) ? 'toRfc4122' : 'toBinary';

		if ($value instanceof IUuid) {
			return $value->$toString();
		}

		if (null === $value || '' === $value) {
			return null;
		}

		if (!\is_string($value)) {
			$this->throwInvalidType($value);
		}

		try {
			return Server::get(IUuidBuilder::class)->fromString($value)->$toString();
		} catch (\InvalidArgumentException $e) {
			$this->throwValueNotConvertible($value, $e);
		}
	}

	public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
		return true;
	}

	private function hasNativeGuidType(AbstractPlatform $platform): bool {
		return $platform->getGuidTypeDeclarationSQL([]) !== $platform->getStringTypeDeclarationSQL(['fixed' => true, 'length' => 36]);
	}

	private function throwInvalidType(mixed $value): never {
		throw ConversionException::conversionFailedInvalidType($value, $this->lookupName($this), ['null', 'string', IUuid::class]);
	}

	private function throwValueNotConvertible(mixed $value, \Throwable $previous): never {
		throw ConversionException::conversionFailed($value, $this->getName(), $previous);
	}
}
