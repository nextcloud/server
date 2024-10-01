<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;

/**
 * @method void setUserId(string $userId)
 * @method string getUserId()
 *
 * @method void setFileId(int $fileId)
 * @method int getFileId()
 *
 * @method void setDueDate(DateTime $dueDate)
 * @method DateTime getDueDate()
 *
 * @method void setUpdatedAt(DateTime $updatedAt)
 * @method DateTime getUpdatedAt()
 *
 * @method void setCreatedAt(DateTime $createdAt)
 * @method DateTime getCreatedAt()
 *
 * @method void setNotified(bool $notified)
 * @method bool getNotified()
 */
class Reminder extends Entity {
	protected $userId;
	protected $fileId;
	protected $dueDate;
	protected $updatedAt;
	protected $createdAt;
	protected $notified = false;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('fileId', 'integer');
		$this->addType('dueDate', 'datetime');
		$this->addType('updatedAt', 'datetime');
		$this->addType('createdAt', 'datetime');
		$this->addType('notified', 'boolean');
	}
}
