<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setUserid(string $userid)
 * @method string getUserid()
 * @method void setGroupid(string $groupid)
 * @method string getGroupid()
 */
class GroupMembership extends Entity {
	/** @var string */
	protected $groupid;

	/** @var string */
	protected $userid;

	public function __construct() {
		$this->addType('groupid', 'string');
		$this->addType('userid', 'string');
	}
}
