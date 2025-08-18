<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCP\AppFramework\Utility\ITimeFactory;

class UUIDFixGroup extends UUIDFix {
	public function __construct(ITimeFactory $time, GroupMapping $mapper, Group_Proxy $proxy) {
		parent::__construct($time);
		$this->mapper = $mapper;
		$this->proxy = $proxy;
	}
}
