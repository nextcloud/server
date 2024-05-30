<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_Proxy;
use OCP\AppFramework\Utility\ITimeFactory;

class UUIDFixUser extends UUIDFix {
	public function __construct(ITimeFactory $time, UserMapping $mapper, User_Proxy $proxy) {
		parent::__construct($time);
		$this->mapper = $mapper;
		$this->proxy = $proxy;
	}
}
