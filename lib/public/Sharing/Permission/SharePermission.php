<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Permission;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingPermission from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class SharePermission {
	public function __construct(
		/** @var class-string<ISharePermissionType> $class */
		public string $class,
		public bool $enabled,
	) {
	}

	/**
	 * @return SharingPermission
	 */
	public function format(): array {
		if (($permissionType = (Server::get(IRegistry::class)->getPermissionTypes()[$this->class] ?? null)) === null) {
			throw new ShareInvalidException('The permission type is not registered: ' . $this->class);
		}

		return [
			'class' => $this->class,
			'display_name' => $permissionType->getDisplayName(),
			'category' => $permissionType->getCategory(),
			'enabled' => $this->enabled,
		];
	}
}
