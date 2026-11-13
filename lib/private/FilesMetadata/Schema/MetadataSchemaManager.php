<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Schema;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataValidationException;
use OCP\FilesMetadata\Schema\IMetadataSchemaManager;
use OCP\FilesMetadata\Schema\MetadataField;
use OCP\FilesMetadata\Schema\MetadataFieldType;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Throwable;

class MetadataSchemaManager implements IMetadataSchemaManager {
	/**
	 * @param string|null $appId app the manager is injected into, passed on to access checks
	 */
	public function __construct(
		private MetadataSchemaRegistry $registry,
		private LoggerInterface $logger,
		private ?string $appId = null,
	) {
	}

	#[\Override]
	public function getFields(): array {
		return $this->registry->getFields();
	}

	#[\Override]
	public function validateValue(string $key, mixed $value): string|int|float|bool {
		$field = $this->registry->getFields()[$key] ?? null;
		if ($field === null) {
			throw new FilesMetadataNotFoundException('No metadata field registered for "' . $key . '"');
		}

		$normalized = match ($field->getType()) {
			MetadataFieldType::STRING => is_string($value) ? $value : null,
			MetadataFieldType::INT => $this->normalizeInt($value),
			MetadataFieldType::FLOAT => $this->normalizeFloat($value),
			MetadataFieldType::BOOL => $this->normalizeBool($value),
			MetadataFieldType::DATE => $this->normalizeDate($value),
			MetadataFieldType::ENUM => $this->normalizeEnum($field, $value),
		};
		if ($normalized === null) {
			throw new FilesMetadataValidationException('Invalid value for metadata field "' . $key . '" of type ' . $field->getType()->value);
		}

		try {
			$this->registry->getProvider($key)?->validateValue($field, $normalized);
		} catch (FilesMetadataValidationException $e) {
			throw $e;
		} catch (Throwable $e) {
			$this->logger->error('Failed to validate value of metadata field "' . $key . '"', [
				'app' => $field->getApp(),
				'exception' => $e,
			]);
			throw new FilesMetadataValidationException('Could not validate value for metadata field "' . $key . '"', previous: $e);
		}

		return $normalized;
	}

	#[\Override]
	public function canRead(string $key, Node $node, ?IUser $user): bool {
		return $this->checkAccess($key, $node, $user, false);
	}

	#[\Override]
	public function canEdit(string $key, Node $node, ?IUser $user): bool {
		return $this->checkAccess($key, $node, $user, true);
	}

	private function checkAccess(string $key, Node $node, ?IUser $user, bool $edit): bool {
		$field = $this->registry->getFields()[$key] ?? null;
		$provider = $this->registry->getProvider($key);
		if ($field === null || $provider === null) {
			return false;
		}

		try {
			return $edit
				? $provider->canEdit($field, $node, $user, $this->appId)
				: $provider->canRead($field, $node, $user, $this->appId);
		} catch (Throwable $e) {
			$this->logger->error('Failed to check access to metadata field "' . $key . '"', [
				'app' => $field->getApp(),
				'exception' => $e,
			]);
			return false;
		}
	}

	private function normalizeInt(mixed $value): ?int {
		if (is_int($value)) {
			return $value;
		}

		return is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : null;
	}

	private function normalizeFloat(mixed $value): ?float {
		if (is_int($value) || is_float($value)) {
			$value = (float)$value;
		} elseif (is_string($value)) {
			$value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
		} else {
			return null;
		}

		return $value !== null && is_finite($value) ? $value : null;
	}

	private function normalizeBool(mixed $value): ?bool {
		return match ($value) {
			true, '1', 'true' => true,
			false, '0', 'false' => false,
			default => null,
		};
	}

	private function normalizeDate(mixed $value): ?int {
		if ($value instanceof DateTimeInterface) {
			return $value->getTimestamp();
		}
		if (is_int($value)) {
			return $value;
		}
		if (!is_string($value)) {
			return null;
		}

		foreach ([DateTimeInterface::ATOM, '!Y-m-d'] as $format) {
			$date = DateTimeImmutable::createFromFormat($format, $value);
			// out of range parts such as a 13th month are rolled over and only reported as warnings
			$errors = DateTimeImmutable::getLastErrors();
			if ($date !== false && ($errors === false || $errors['warning_count'] === 0)) {
				return $date->getTimestamp();
			}
		}

		return null;
	}

	private function normalizeEnum(MetadataField $field, mixed $value): ?string {
		foreach ($field->getOptions() ?? [] as $option) {
			if ($option->getValue() === $value) {
				return $value;
			}
		}

		return null;
	}
}
