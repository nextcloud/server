<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Storage;

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Share\IShare;

class PublicShareWrapper extends Wrapper {

	private IShare $share;

	/**
	 * @param array $arguments ['storage' => $storage, 'share' => $share]
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $share: The share to use in case no share is found
	 */
	public function __construct($arguments) {
		parent::__construct($arguments);
		$this->share = $arguments['share'];
	}

	public function getShare() {
		$storage = parent::getWrapperStorage();
		if (method_exists($storage, 'getShare')) {
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			return $storage->getShare();
		}

		return $this->share;
	}
}
