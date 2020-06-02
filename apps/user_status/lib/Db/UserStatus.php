<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
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
 * @method string getMessageId()
 * @method void setMessageId(string|null $messageId)
 * @method string getCustomIcon()
 * @method void setCustomIcon(string|null $customIcon)
 * @method string getCustomMessage()
 * @method void setCustomMessage(string|null $customMessage)
 * @method int getClearAt()
 * @method void setClearAt(int|null $clearAt)
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

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('status', 'string');
		$this->addType('statusTimestamp', 'int');
		$this->addType('isUserDefined', 'boolean');
		$this->addType('messageId', 'string');
		$this->addType('customIcon', 'string');
		$this->addType('customMessage', 'string');
		$this->addType('clearAt', 'int');
	}
}
