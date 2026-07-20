<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\AppInfo\Application;
use OCP\Capabilities\ICapability;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Permission\ISharePermissionPreset;
use OCP\Sharing\Source\IShareSourceType;

/**
 * @psalm-import-type SharingSourceType from ResponseDefinitions
 * @psalm-import-type SharingPermissionPreset from ResponseDefinitions
 */
final readonly class Capabilities implements ICapability {
	public function __construct(
		private IFactory $l10nFactory,
		private ISharingRegistry $registry,
	) {
	}

	/**
	 * @return array{
	 *     sharing?: array{
	 *         api_versions: list<'v1'>,
	 *         source_types: list<SharingSourceType>,
	 *         permission_presets: list<SharingPermissionPreset>,
	 *     },
	 * }
	 */
	#[\Override]
	public function getCapabilities(): array {
		if (!Server::get(IManager::class)->shareApiEnabled()) {
			return [];
		}

		$sourceTypes = array_map(static fn (IShareSourceType $sourceType): array => [
			'class' => $sourceType::class,
		], array_values($this->registry->getSourceTypes()));

		$permissionPresets = array_map(fn (ISharePermissionPreset $permissionPreset): array => [
			'class' => $permissionPreset::class,
			'display_name' => $permissionPreset->getDisplayName($this->l10nFactory),
			'hint' => $permissionPreset->getHint($this->l10nFactory),
		], array_values($this->registry->getPermissionPresets()));

		return [
			Application::APP_ID => [
				'api_versions' => ['v1'],
				'source_types' => $sourceTypes,
				'permission_presets' => $permissionPresets,
			],
		];
	}
}
