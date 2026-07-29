<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Permission;

use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Share;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use RuntimeException;

/**
 * @psalm-import-type SharingPermission from Share
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class SharePermission {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var class-string<ISharePermissionType> $class */
		public string $class,
		public bool $enabled,
	) {
	}

	/**
	 * @return SharingPermission
	 * @experimental 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory): array {
		if (($permissionType = ($registry->getPermissionTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The permission type is not registered: ' . $this->class);
		}

		return [
			'class' => $this->class,
			'source_class' => $registry->getPermissionTypeSourceTypeClass()[$this->class],
			'display_name' => $permissionType->getDisplayName($l10nFactory),
			'hint' => $permissionType->getHint($l10nFactory),
			'priority' => $permissionType->getPriority(),
			'presets' => $registry->getPermissionTypeCompatiblePermissionPresetClasses()[$permissionType::class] ?? [],
			'enabled' => $this->enabled,
		];
	}
}
