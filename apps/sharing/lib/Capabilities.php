<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\AppInfo\Application;
use OCP\Capabilities\ICapability;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Source\IShareSourceType;

/**
 * @psalm-import-type SharingSourceType from ResponseDefinitions
 */
final readonly class Capabilities implements ICapability {
	public function __construct(
		private ISharingRegistry $registry,
	) {
	}

	/**
	 * @return array{
	 *     sharing?: array{
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
	 *         source_types: list<SharingSourceType>,
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

		return [
			Application::APP_ID => [
				'api_versions' => ['v1'],
				'legacy' => [
					'max_sources' => 1,
					'max_recipients' => 1,
				],
				'source_types' => $sourceTypes,
			],
		];
	}
}
