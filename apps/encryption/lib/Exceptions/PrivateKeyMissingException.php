<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Exceptions;

use OCP\Encryption\Exceptions\GenericEncryptionException;

class PrivateKeyMissingException extends GenericEncryptionException {

	/**
	 * @param string $userId
	 */
	public function __construct($userId) {
		if (empty($userId)) {
			$userId = '<no-user-id-given>';
		}
		parent::__construct("Private Key missing for user: $userId");
	}
}
