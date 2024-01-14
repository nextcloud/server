<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
