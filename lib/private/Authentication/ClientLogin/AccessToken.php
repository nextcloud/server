<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Authentication\ClientLogin;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getToken()
 * @method void setToken(string $accessToken)
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method string getClientName()
 * @method void setClientName(string $name)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $created)
 */
class AccessToken extends Entity {

	const STATUS_PENDING = 0;
	const STATUS_FINISHED = 1;

	/** @var string hashed access token */
	protected $token;

	/** @var string user UID (only set on finished tokens) */
	protected $uid;

	/** @var string client name */
	protected $clientName;

	/** @var int login status */
	protected $status;

	/** @var timestamp */
	protected $createdAt;

}
