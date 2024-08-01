<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getTokenId()
 * @method void setTokenId(int $identifier)
 * @method int getClientId()
 * @method void setClientId(int $identifier)
 * @method string getEncryptedToken()
 * @method void setEncryptedToken(string $token)
 * @method string getHashedCode()
 * @method void setHashedCode(string $token)
 * @method int getCodeCreatedAt()
 * @method void setCodeCreatedAt(int $createdAt)
 * @method int getTokenCount()
 * @method void setTokenCount(int $tokenCount)
 */
class AccessToken extends Entity {
	/** @var int */
	protected $tokenId;
	/** @var int */
	protected $clientId;
	/** @var string */
	protected $hashedCode;
	/** @var string */
	protected $encryptedToken;
	/** @var int */
	protected $codeCreatedAt;
	/** @var int */
	protected $tokenCount;

	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('tokenId', 'int');
		$this->addType('clientId', 'int');
		$this->addType('hashedCode', 'string');
		$this->addType('encryptedToken', 'string');
		$this->addType('codeCreatedAt', 'int');
		$this->addType('tokenCount', 'int');
	}
}
