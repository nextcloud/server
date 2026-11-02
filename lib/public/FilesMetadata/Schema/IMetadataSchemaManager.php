<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Schema;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataValidationException;
use OCP\IUser;

/**
 * Access to the metadata fields registered by apps.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IMetadataSchemaManager {
	/**
	 * All registered fields
	 *
	 * @return array<string, MetadataField> fields indexed by their key
	 * @since 36.0.0
	 */
	public function getFields(): array;

	/**
	 * Validate a value for a field and return it normalized for storage.
	 *
	 * The value must match the field type, then the app owning the field validates it.
	 * - {@see MetadataFieldType::STRING}: a string
	 * - {@see MetadataFieldType::INT}: an int or a string containing one
	 * - {@see MetadataFieldType::FLOAT}: a finite int, float or a string containing one
	 * - {@see MetadataFieldType::BOOL}: a bool or one of `'1'`, `'0'`, `'true'`, `'false'`
	 * - {@see MetadataFieldType::DATE}: a {@see \DateTimeInterface}, a unix timestamp or an ISO 8601 string, normalized to a unix timestamp
	 * - {@see MetadataFieldType::ENUM}: the value of one of the field options
	 *
	 * @param string $key the key of the metadata field
	 * @param mixed $value the value to validate
	 * @throws FilesMetadataNotFoundException if no field is registered with this key
	 * @throws FilesMetadataValidationException if the value is not valid for the field
	 * @since 36.0.0
	 */
	public function validateValue(string $key, mixed $value): string|int|float|bool;

	/**
	 * Whether the user may see the value of the field on the node, as decided by the app owning the field.
	 * Returns false for unknown fields.
	 * When injected into an app, the app is detected and passed on to the owning app.
	 *
	 * @param string $key the key of the metadata field
	 * @param Node $node the node to which the metadata key applies
	 * @param IUser|null $user null for unauthenticated access, e.g. public shares
	 * @since 36.0.0
	 */
	public function canRead(string $key, Node $node, ?IUser $user): bool;

	/**
	 * Whether the user may change the value of the field on the node, as decided by the app owning the field.
	 * Returns false for unknown fields.
	 * When injected into an app, the app is detected and passed on to the owning app.
	 *
	 * @param string $key the key of the metadata field
	 * @param Node $node the node to which the metadata key applies
	 * @param IUser|null $user null for unauthenticated access, e.g. public shares
	 * @since 36.0.0
	 */
	public function canEdit(string $key, Node $node, ?IUser $user): bool;
}
