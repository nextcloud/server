<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption\Exceptions;

use OCP\Encryption\Exceptions\GenericEncryptionException;

class ModuleAlreadyExistsException extends GenericEncryptionException {
	/**
	 * @param string $id
	 * @param string $name
	 */
	public function __construct($id, $name) {
		parent::__construct('Id "' . $id . '" already used by encryption module "' . $name . '"');
	}
}
