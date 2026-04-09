<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setSourceUser(string $uid)
 * @method string getSourceUser()
 * @method void setTargetUser(string $uid)
 * @method string getTargetUser()
 * @method void setFileId(int $fileId)
 * @method int getFileId()
 * @method void setNodeName(string $name)
 * @method string getNodeName()
 */
class TransferOwnership extends Entity {
	/** @var string */
	protected $sourceUser;

	/** @var string */
	protected $targetUser;

	/** @var integer */
	protected $fileId;

	/** @var string */
	protected $nodeName;

	public function __construct() {
		$this->addType('sourceUser', 'string');
		$this->addType('targetUser', 'string');
		$this->addType('fileId', 'integer');
		$this->addType('nodeName', 'string');
	}
}
