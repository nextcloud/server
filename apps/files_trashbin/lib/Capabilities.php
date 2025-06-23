<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin;

use OCA\Files_Trashbin\Service\ConfigService;
use OCP\Capabilities\ICapability;

/**
 * Class Capabilities
 *
 * @package OCA\Files_Trashbin
 */
class Capabilities implements ICapability {

	/**
	 * Return this classes capabilities
	 *
	 * @return array{
	 *     files: array{
	 *         undelete: bool,
	 *         delete_from_trash: bool
	 *     }
	 * }
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'undelete' => true,
				'delete_from_trash' => ConfigService::getDeleteFromTrashEnabled(),
			]
		];
	}
}
