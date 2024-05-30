<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Storage;

use OC\Files\Storage\Wrapper\Wrapper;

class PublicOwnerWrapper extends Wrapper {

	/** @var string */
	private $owner;

	/**
	 * @param array $arguments ['storage' => $storage, 'owner' => $owner]
	 *
	 * $storage: The storage the permissions mask should be applied on
	 * $owner: The owner to use in case no owner is found
	 */
	public function __construct($arguments) {
		parent::__construct($arguments);
		$this->owner = $arguments['owner'];
	}

	public function getOwner($path) {
		$owner = parent::getOwner($path);

		if ($owner === null || $owner === false) {
			return $this->owner;
		}

		return $owner;
	}
}
