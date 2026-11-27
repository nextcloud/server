<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Schema;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Files\Node;
use OCP\FilesMetadata\Exceptions\FilesMetadataValidationException;
use OCP\IUser;

/**
 * Implemented by apps to register metadata fields and decide who can access them.
 *
 * Register with {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerMetadataSchemaProvider()}.
 *
 * @since 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IMetadataSchemaProvider {
	/**
	 * Fields owned by this provider
	 *
	 * @return array<MetadataField>
	 * @since 36.0.0
	 */
	public function getFields(): array;

	/**
	 * Validate a value already checked against the field type, e.g. to restrict its format or range
	 *
	 * @param MetadataField $field the metadata field the value is set for
	 * @param string|int|float|bool $value the value normalized for storage
	 * @throws FilesMetadataValidationException if the value is not valid
	 * @since 36.0.0
	 */
	public function validateValue(MetadataField $field, string|int|float|bool $value): void;

	/**
	 * Whether the user may see the value of the field on the node
	 *
	 * @param MetadataField $field the metadata field to check for readability
	 * @param Node $node the file to which the metadata field will apply
	 * @param IUser|null $user null for unauthenticated access, e.g. public shares
	 * @param string|null $appId app performing the check, null when not called by an app, e.g. from WebDAV
	 * @since 36.0.0
	 */
	public function canRead(MetadataField $field, Node $node, ?IUser $user, ?string $appId): bool;

	/**
	 * Whether the user may change the value of the field on the node
	 *
	 * @param MetadataField $field the metadata field to check for editability
	 * @param Node $node the file to which the metadata field will apply
	 * @param IUser|null $user null for unauthenticated access, e.g. public shares
	 * @param string|null $appId app performing the check, null when not called by an app, e.g. from WebDAV
	 * @since 36.0.0
	 */
	public function canEdit(MetadataField $field, Node $node, ?IUser $user, ?string $appId): bool;
}
