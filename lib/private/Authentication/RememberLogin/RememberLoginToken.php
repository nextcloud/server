<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\RememberLogin;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method void setUid(string $uid)
 * @method string getUid()
 * @method void setToken(string $token)
 * @method string getToken()
 * @method void setCreated(int $created)
 * @method int getCreated()
 */
class RememberLoginToken extends Entity {
	/** @var string */
	protected $uid;

	/** @var string */
	protected $token;

	/** @var int */
	protected $created;

	public function __construct() {
		$this->addType('uid', Types::STRING);
		$this->addType('token', Types::STRING);
		$this->addType('created', Types::INTEGER);
	}
}
