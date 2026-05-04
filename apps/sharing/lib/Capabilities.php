<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\AppInfo\Application;
use OCP\Capabilities\ICapability;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Permission\ISharePermissionCategoryType;

final readonly class Capabilities implements ICapability {
	public function __construct(
		private IRegistry $registry,
	) {
	}

	/**
	 * @return array{
	 *     sharing: array{
	 *         api_versions: list<'v1'>,
	 *         // Information for legacy compatibility with files_sharing.
	 *         // As long as Unified Sharing is a translation layer to files_sharing this is needed.
	 *         // As soon as this turns around and files_sharing becomes a translation layer for Unified Sharing,
	 *         // this information will be removed.
	 *         legacy?: array{
	 *             // Maximum number of sources allowed to be selected by the user.
	 *             max_sources: positive-int,
	 *             // Maximum number of recipients allowed to be selected by the user.
	 *             max_recipients: positive-int,
	 *         },
	 *         permission_categories: list<array{
	 *             class: class-string<ISharePermissionCategoryType>,
	 *             display_name: non-empty-string,
	 *         }>,
	 *     },
	 * }
	 */
	#[\Override]
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'api_versions' => ['v1'],
				'legacy' => [
					'max_sources' => 1,
					'max_recipients' => 1,
				],
				'permission_categories' => array_map(static fn (ISharePermissionCategoryType $permissionCategoryType): array => [
					'class' => $permissionCategoryType::class,
					'display_name' => $permissionCategoryType->getDisplayName(),
				], array_values($this->registry->getPermissionCategoryTypes())),
			],
		];
	}
}
