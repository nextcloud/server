<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getClientIdentifier()
 * @method void setClientIdentifier(string $identifier)
 * @method string getSecret()
 * @method void setSecret(string $secret)
 * @method string getRedirectUri()
 * @method void setRedirectUri(string $redirectUri)
 * @method string getName()
 * @method void setName(string $name)
 */
class Client extends Entity {
	/** @var string */
	protected $name;
	/** @var string */
	protected $redirectUri;
	/** @var string */
	protected $clientIdentifier;
	/** @var string */
	protected $secret;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('name', 'string');
		$this->addType('redirectUri', 'string');
		$this->addType('clientIdentifier', 'string');
		$this->addType('secret', 'string');
	}
}
