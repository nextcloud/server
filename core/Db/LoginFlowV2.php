<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 * @method int getStarted()
 * @method void setStarted(int $started)
 * @method string getPollToken()
 * @method void setPollToken(string $token)
 * @method string getLoginToken()
 * @method void setLoginToken(string $token)
 * @method string getPublicKey()
 * @method void setPublicKey(string $key)
 * @method string getPrivateKey()
 * @method void setPrivateKey(string $key)
 * @method string getClientName()
 * @method void setClientName(string $clientName)
 * @method string getLoginName()
 * @method void setLoginName(string $loginName)
 * @method string getServer()
 * @method void setServer(string $server)
 * @method string getAppPassword()
 * @method void setAppPassword(string $appPassword)
 */
class LoginFlowV2 extends Entity {
	/** @var int */
	protected $timestamp;
	/** @var int */
	protected $started;
	/** @var string */
	protected $pollToken;
	/** @var string */
	protected $loginToken;
	/** @var string */
	protected $publicKey;
	/** @var string */
	protected $privateKey;
	/** @var string */
	protected $clientName;
	/** @var string */
	protected $loginName;
	/** @var string */
	protected $server;
	/** @var string */
	protected $appPassword;

	public function __construct() {
		$this->addType('timestamp', Types::INTEGER);
		$this->addType('started', Types::INTEGER);
		$this->addType('pollToken', 'string');
		$this->addType('loginToken', 'string');
		$this->addType('publicKey', 'string');
		$this->addType('privateKey', 'string');
		$this->addType('clientName', 'string');
		$this->addType('loginName', 'string');
		$this->addType('server', 'string');
		$this->addType('appPassword', 'string');
	}
}
