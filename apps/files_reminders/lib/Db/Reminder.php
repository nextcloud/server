<?php

declare(strict_types=1);

/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
