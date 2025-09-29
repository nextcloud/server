<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth;

/**
 * Invalid authentication representing an auth mechanism
 * that could not be resolved0
 */
class InvalidAuth extends AuthMechanism {

	/**
	 * Constructs a new InvalidAuth with the id of the invalid auth
	 * for display purposes
	 *
	 * @param string $invalidId invalid id
	 */
	public function __construct($invalidId) {
		$this
			->setIdentifier($invalidId)
			->setScheme(self::SCHEME_NULL)
			->setText('Unknown auth mechanism backend ' . $invalidId)
		;
	}
}
