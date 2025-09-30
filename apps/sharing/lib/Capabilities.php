<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing;

use OCA\Sharing\AppInfo\Application;
use OCA\Sharing\Model\AShareFeature;
use OCA\Sharing\Model\AShareRecipientType;
use OCA\Sharing\Model\AShareSourceType;
use OCP\Capabilities\ICapability;

/**
 * @psalm-import-type SharingFeature from ResponseDefinitions
 */
class Capabilities implements ICapability {
	public function __construct(
		private readonly Registry $registry,
	) {
	}

	/**
	 * @return array{
	 *     sharing: array{
	 *         source_types: list<class-string<AShareSourceType>>,
	 *         recipient_types: list<class-string<AShareRecipientType>>,
	 *         features: array<class-string<AShareFeature>, SharingFeature>,
	 *     },
	 * }
	 */
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'source_types' => array_keys($this->registry->getSourceTypes()),
				'recipient_types' => array_keys($this->registry->getRecipientTypes()),
				'features' => array_map(static fn (AShareFeature $feature): array => $feature->toArray(), $this->registry->getFeatures()),
			],
		];
	}
}
