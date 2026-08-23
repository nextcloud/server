<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Failure;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getFileId()
 * @method void setFileId(int $fileId)
 * @method string|null getUserId()
 * @method void setUserId(?string $userId)
 * @method string|null getPath()
 * @method void setPath(?string $path)
 * @method string getMime()
 * @method void setMime(string $mime)
 * @method string|null getProvider()
 * @method void setProvider(?string $provider)
 * @method string getError()
 * @method void setError(string $error)
 * @method int getAttempts()
 * @method void setAttempts(int $attempts)
 * @method int getLastAttempt()
 * @method void setLastAttempt(int $lastAttempt)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class PreviewFailure extends Entity {
	protected int $fileId = 0;
	protected ?string $userId = null;
	protected ?string $path = null;
	protected string $mime = '';
	protected ?string $provider = null;
	protected string $error = '';
	protected int $attempts = 1;
	protected int $lastAttempt = 0;
	protected int $createdAt = 0;

	public function __construct() {
		$this->addType('fileId', Types::BIGINT);
		$this->addType('userId', Types::STRING);
		$this->addType('path', Types::STRING);
		$this->addType('mime', Types::STRING);
		$this->addType('provider', Types::STRING);
		$this->addType('error', Types::STRING);
		$this->addType('attempts', Types::INTEGER);
		$this->addType('lastAttempt', Types::INTEGER);
		$this->addType('createdAt', Types::INTEGER);
	}
}
