<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Storage;

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Storage\ISharedStorage;
use OCP\Share\IShare;

class PublicShareWrapper extends Wrapper implements ISharedStorage {

	private IShare $share;

	/**
	 * @param array $parameters ['storage' => $storage, 'share' => $share]
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $share: The share to use in case no share is found
	 */
	public function __construct(array $parameters) {
		parent::__construct($parameters);
		$this->share = $parameters['share'];
	}

	public function getShare(): IShare {
		$storage = parent::getWrapperStorage();
		if (method_exists($storage, 'getShare')) {
			/** @var ISharedStorage $storage */
			return $storage->getShare();
		}

		return $this->share;
	}
}
