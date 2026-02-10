<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setId(int $id)
 * @method int getId()
 * @method void setUserId(string $userId)
 * @method string getUserId()
 * @method void setToken(string $token)
 * @method string getToken()
 * @method void setPath(string $path)
 * @method string getPath()
 * @method void setSize(int|null $size)
 * @method int|null getSize()
 * @method void setComplete(bool|null $complete)
 * @method bool|null getComplete()
 */
class ResumableUpload extends Entity {
	public $id;

	protected string $userId = '';

	protected string $token = '';

	protected string $path = '';

	protected ?int $size = null;

	protected ?bool $complete = false;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('token', 'string');
		$this->addType('path', 'string');
		$this->addType('size', 'integer');
		$this->addType('complete', 'boolean');
	}
}
