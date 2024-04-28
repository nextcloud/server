<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class UserStatus
 *
 * @package OCA\UserStatus\Db
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method int getStatusTimestamp()
 * @method void setStatusTimestamp(int $statusTimestamp)
 * @method bool getIsUserDefined()
 * @method void setIsUserDefined(bool $isUserDefined)
 * @method string|null getMessageId()
 * @method void setMessageId(string|null $messageId)
 * @method string|null getCustomIcon()
 * @method void setCustomIcon(string|null $customIcon)
 * @method string|null getCustomMessage()
 * @method void setCustomMessage(string|null $customMessage)
 * @method int|null getClearAt()
 * @method void setClearAt(int|null $clearAt)
 * @method setIsBackup(bool $isBackup): void
 * @method getIsBackup(): bool
 * @method int getStatusMessageTimestamp()
 * @method void setStatusMessageTimestamp(int $statusTimestamp)
 */
class UserStatus extends Entity {

	/** @var string */
	public $userId;

	/** @var string */
	public $status;

	/** @var int */
	public $statusTimestamp;

	/** @var boolean */
	public $isUserDefined;

	/** @var string|null */
	public $messageId;

	/** @var string|null */
	public $customIcon;

	/** @var string|null */
	public $customMessage;

	/** @var int|null */
	public $clearAt;

	/** @var bool $isBackup */
	public $isBackup;

	/** @var int */
	protected $statusMessageTimestamp = 0;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('status', 'string');
		$this->addType('statusTimestamp', 'int');
		$this->addType('isUserDefined', 'boolean');
		$this->addType('messageId', 'string');
		$this->addType('customIcon', 'string');
		$this->addType('customMessage', 'string');
		$this->addType('clearAt', 'int');
		$this->addType('isBackup', 'boolean');
		$this->addType('statusMessageTimestamp', 'int');
	}
}
