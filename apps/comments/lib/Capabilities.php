<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	/**
	 * @return array{files: array{comments: bool}}
	 */
	public function getCapabilities(): array {
		return [
			'files' => [
				'comments' => true,
			]
		];
	}
}
