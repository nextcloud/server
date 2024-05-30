<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
