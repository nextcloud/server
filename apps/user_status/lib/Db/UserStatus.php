<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

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
		$this->addType('statusTimestamp', Types::INTEGER);
		$this->addType('isUserDefined', 'boolean');
		$this->addType('messageId', 'string');
		$this->addType('customIcon', 'string');
		$this->addType('customMessage', 'string');
		$this->addType('clearAt', Types::INTEGER);
		$this->addType('isBackup', 'boolean');
		$this->addType('statusMessageTimestamp', Types::INTEGER);
	}
}
