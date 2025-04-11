<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\DAV;

use OCA\Federation\DbHandler;
use OCP\Defaults;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class FedAuth extends AbstractBasic {

	/**
	 * FedAuth constructor.
	 *
	 * @param DbHandler $db
	 */
	public function __construct(
		private DbHandler $db,
	) {
		$this->principalPrefix = 'principals/system/';

		// setup realm
		$defaults = new Defaults();
		$this->realm = $defaults->getName();
	}

	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function validateUserPass($username, $password) {
		return $this->db->auth($username, $password);
	}

	/**
	 * @inheritdoc
	 */
	public function challenge(RequestInterface $request, ResponseInterface $response) {
	}
}
