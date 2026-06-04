<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Proxy;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getOwnerId()
 * @method void setOwnerId(string $ownerId)
 * @method string getProxyId()
 * @method void setProxyId(string $proxyId)
 * @method int getPermissions()
 * @method void setPermissions(int $permissions)
 */
class Proxy extends Entity {

	/** @var string */
	protected $ownerId;
	/** @var string */
	protected $proxyId;
	/** @var int */
	protected $permissions;

	public function __construct() {
		$this->addType('ownerId', Types::STRING);
		$this->addType('proxyId', Types::STRING);
		$this->addType('permissions', Types::INTEGER);
	}
}
