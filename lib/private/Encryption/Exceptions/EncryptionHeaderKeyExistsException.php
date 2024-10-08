<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption\Exceptions;

use OCP\Encryption\Exceptions\GenericEncryptionException;

class EncryptionHeaderKeyExistsException extends GenericEncryptionException {
	/**
	 * @param string $key
	 */
	public function __construct($key) {
		parent::__construct('header key "' . $key . '" already reserved by ownCloud');
	}
}
