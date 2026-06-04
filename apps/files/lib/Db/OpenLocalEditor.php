<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setUserId(string $userId)
 * @method string getUserId()
 * @method void setPathHash(string $pathHash)
 * @method string getPathHash()
 * @method void setExpirationTime(int $expirationTime)
 * @method int getExpirationTime()
 * @method void setToken(string $token)
 * @method string getToken()
 */
class OpenLocalEditor extends Entity {
	/** @var string */
	protected $userId;

	/** @var string */
	protected $pathHash;

	/** @var int */
	protected $expirationTime;

	/** @var string */
	protected $token;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('pathHash', 'string');
		$this->addType('expirationTime', 'integer');
		$this->addType('token', 'string');
	}
}
