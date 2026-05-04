<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Sharing\Permission\ISharePermissionCategoryType;
use OCP\Sharing\Permission\ISharePermissionType;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ISharePropertyType;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\IShareRecipientType;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Source\IShareSourceType;
use OCP\Sharing\Source\ShareSource;

/**
 * Keep the following types in sync with apps/sharing/lib/ResponseDefinitions.php:
 *
 * @psalm-type SharingSource = array{
 *     class: class-string<IShareSourceType>,
 *     value: non-empty-string,
 *     display_name: non-empty-string,
 * }
 *
 * @psalm-type SharingIconSVG = array{
 *     svg: non-empty-string,
 * }
 *
 * @psalm-type SharingIconURL = array{
 *     // An absolute URL to an image suitable for light theme.
 *     light: non-empty-string,
 *     // An absolute URL to an image suitable for dark theme.
 *     dark: non-empty-string,
 * }
 *
 * @psalm-type SharingIcon = SharingIconSVG|SharingIconURL
 *
 * @psalm-type SharingRecipient = array{
 *     class: class-string<IShareRecipientType>,
 *     value: non-empty-string,
 *     instance?: non-empty-string,
 *     display_name: non-empty-string,
 *     icon?: SharingIcon,
 * }
 *
 * @psalm-type SharingOwner = array{
 *     user_id: non-empty-string,
 *     instance?: non-empty-string,
 *     display_name: non-empty-string,
 *     icon: SharingIconURL,
 * }
 *
 * @psalm-type SharingState = 'active'|'draft'|'deleted'
 *
 * @psalm-type SharingProperty = array{
 *     class: class-string<ISharePropertyType>,
 *     display_name: non-empty-string,
 *     hint?: non-empty-string,
 *     priority: int<1, 100>,
 *     required: bool,
 *     value: ?string,
 * }
 *
 * @psalm-type SharingPropertyBoolean = SharingProperty&array{
 *     type: 'boolean',
 * }
 *
 * @psalm-type SharingPropertyDate = SharingProperty&array{
 *     type: 'date',
 *     // ISO 8601
 *     min_date?: non-empty-string,
 *     // ISO 8601
 *     max_date?: non-empty-string,
 * }
 *
 * @psalm-type SharingPropertyEnum = SharingProperty&array{
 *     type: 'enum',
 *     valid_values: non-empty-list<string>,
 * }
 *
 * @psalm-type SharingPropertyPassword = SharingProperty&array{
 *     type: 'password',
 * }
 *
 * @psalm-type SharingPropertyString = SharingProperty&array{
 *     type: 'string',
 *     min_length?: positive-int,
 *     max_length?: positive-int,
 * }
 *
 * @psalm-type SharingPermission = array{
 *     class: class-string<ISharePermissionType>,
 *     display_name: non-empty-string,
 *     category: ?class-string<ISharePermissionCategoryType>,
 *     enabled: bool,
 * }
 *
 * @psalm-type SharingShare = array{
 *     id: non-empty-string,
 *     owner: SharingOwner,
 *     // Unix time in milliseconds
 *     last_updated: non-negative-int,
 *     state: SharingState,
 *     sources: list<SharingSource>,
 *     recipients: list<SharingRecipient>,
 *     properties: list<SharingPropertyDate|SharingPropertyEnum|SharingPropertyBoolean|SharingPropertyPassword|SharingPropertyString>,
 *     permissions: list<SharingPermission>,
 * }
 *
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class Share {
	public function __construct(
		/** @var non-empty-string $id */
		public string $id,
		public ShareOwner $owner,
		/** @var non-negative-int $lastUpdated Unix time in milliseconds */
		public int $lastUpdated,
		public ShareState $state,
		/** @var list<ShareSource> $sources */
		public array $sources,
		/** @var list<ShareRecipient> $recipients */
		public array $recipients,
		/** @var list<ShareProperty> */
		public array $properties,
		/** @var list<SharePermission> */
		public array $permissions,
	) {
	}

	/**
	 * @return SharingShare
	 */
	public function format(): array {
		return [
			'id' => $this->id,
			'owner' => $this->owner->format(),
			'last_updated' => $this->lastUpdated,
			'state' => $this->state->value,
			'sources' => ShareSource::formatMultiple($this->sources),
			'recipients' => ShareRecipient::formatMultiple($this->recipients),
			'properties' => array_map(static fn (ShareProperty $property): array => $property->format(), $this->properties),
			'permissions' => array_map(static fn (SharePermission $permission): array => $permission->format(), $this->permissions),
		];
	}
}
