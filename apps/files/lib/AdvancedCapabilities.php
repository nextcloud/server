<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files;

use OCA\Files\Service\SettingsService;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;

/**
 * Capabilities not needed for every request.
 * This capabilities might be hard to compute or no used by the webui.
 */
class AdvancedCapabilities implements ICapability, IInitialStateExcludedCapability {

	public function __construct(
		protected SettingsService $service,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{'windows_compatible_filenames': bool}}
	 */
	public function getCapabilities(): array {
		return [
			'files' => [
				'windows_compatible_filenames' => $this->service->hasFilesWindowsSupport(),
			],
		];
	}
}
