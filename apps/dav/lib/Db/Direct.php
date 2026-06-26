<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getFileId()
 * @method void setFileId(int $fileId)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method int getExpiration()
 * @method void setExpiration(int $expiration)
 */
class Direct extends Entity {
	/** @var string */
	protected $userId;

	/** @var int */
	protected $fileId;

	/** @var string */
	protected $token;

	/** @var int */
	protected $expiration;

	public function __construct() {
		$this->addType('userId', Types::STRING);
		$this->addType('fileId', Types::INTEGER);
		$this->addType('token', Types::STRING);
		$this->addType('expiration', Types::INTEGER);
	}
}
