<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Storage;

use OC\Files\Storage\Wrapper\Wrapper;

class PublicOwnerWrapper extends Wrapper {

	private string $owner;

	public function __construct(array $parameters) {
		parent::__construct($parameters);
		$this->owner = $parameters['owner'];
	}

	public function getOwner(string $path): string|false {
		$owner = parent::getOwner($path);
		if ($owner !== false) {
			return $owner;
		}

		return $this->owner;
	}
}
