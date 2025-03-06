<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method ?bool getAccepted()
 * @method void setAccepted(bool $accepted)
 * @method ?int getAcceptedAt()
 * @method void setAcceptedAt(int $acceptedAt)
 * @method ?int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method ?int getExpiredAt()
 * @method void setExpiredAt(int $expiredAt)
 * @method ?string getRecipientEmail()
 * @method void setRecipientEmail(string $recipientEmail)
 * @method ?string getRecipientName()
 * @method void setRecipientName(string $recipientName)
 * @method ?string getRecipientProvider()
 * @method void setRecipientProvider(string $recipientProvider)
 * @method ?string getRecipientUserId()
 * @method void setRecipientUserId(string $recipientUserId)
 * @method ?string getToken()
 * @method void setToken(string $token)
 * @method ?string getUserId()
 * @method void setUserId(string $userId)
 */

class FederatedInvite extends Entity {

	/**
	* @var ?bool $accepted
	*/
	protected $accepted;
	/**
	* @var ?int $acceptedAt
	*/
	protected $acceptedAt;
	/**
	* @var int $createdAt
	*/
	protected $createdAt;
	/**
	* @var $int $expiredAt
	*/
	protected $expiredAt;
	/**
	* @var ?string $recipientEmail
	*/
	protected $recipientEmail;
	/**
	* @var ?string $recipientName
	*/
	protected $recipientName;
	/**
	* @var ?string $recipientProvider
	*/
	protected $recipientProvider;
	/**
	* @var ?string $recipientUserId
	*/
	protected $recipientUserId;
	/**
	* @var string $token
	*/
	protected $token;
	/**
	* @var string $userId
	*/
	protected $userId;

	public function __construct() {
		$this->addType('accepted', Types::BOOLEAN);
		$this->addType('acceptedAt', Types::BIGINT);
		$this->addType('createdAt', Types::BIGINT);
		$this->addType('expiredAt', Types::BIGINT);
		$this->addType('recipientEmail', Types::STRING);
		$this->addType('recipientName', Types::STRING);
		$this->addType('recipientProvider', Types::STRING);
		$this->addType('recipientUserId', Types::STRING);
		$this->addType('token', Types::STRING);
		$this->addType('userId', Types::STRING);
	}
}
