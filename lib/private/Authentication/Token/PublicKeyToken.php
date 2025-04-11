<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Token;

use OCP\AppFramework\Db\Entity;
use OCP\Authentication\Token\IToken;
use OCP\DB\Types;

/**
 * @method void setId(int $id)
 * @method void setUid(string $uid);
 * @method void setLoginName(string $loginname)
 * @method string getToken()
 * @method void setType(int $type)
 * @method int getType()
 * @method void setRemember(int $remember)
 * @method void setLastActivity(int $lastactivity)
 * @method int getLastActivity()
 * @method string getPrivateKey()
 * @method void setPrivateKey(string $key)
 * @method string getPublicKey()
 * @method void setPublicKey(string $key)
 * @method void setVersion(int $version)
 * @method bool getPasswordInvalid()
 * @method string getPasswordHash()
 * @method setPasswordHash(string $hash)
 */
class PublicKeyToken extends Entity implements INamedToken, IWipeableToken {
	public const VERSION = 2;

	/** @var string user UID */
	protected $uid;

	/** @var string login name used for generating the token */
	protected $loginName;

	/** @var string encrypted user password */
	protected $password;

	/** @var string hashed user password */
	protected $passwordHash;

	/** @var string token name (e.g. browser/OS) */
	protected $name;

	/** @var string */
	protected $token;

	/** @var int */
	protected $type;

	/** @var int */
	protected $remember;

	/** @var int */
	protected $lastActivity;

	/** @var int */
	protected $lastCheck;

	/** @var string */
	protected $scope;

	/** @var int */
	protected $expires;

	/** @var string */
	protected $privateKey;

	/** @var string */
	protected $publicKey;

	/** @var int */
	protected $version;

	/** @var bool */
	protected $passwordInvalid;

	public function __construct() {
		$this->addType('uid', 'string');
		$this->addType('loginName', 'string');
		$this->addType('password', 'string');
		$this->addType('passwordHash', 'string');
		$this->addType('name', 'string');
		$this->addType('token', 'string');
		$this->addType('type', Types::INTEGER);
		$this->addType('remember', Types::INTEGER);
		$this->addType('lastActivity', Types::INTEGER);
		$this->addType('lastCheck', Types::INTEGER);
		$this->addType('scope', 'string');
		$this->addType('expires', Types::INTEGER);
		$this->addType('publicKey', 'string');
		$this->addType('privateKey', 'string');
		$this->addType('version', Types::INTEGER);
		$this->addType('passwordInvalid', Types::BOOLEAN);
	}

	public function getId(): int {
		return $this->id;
	}

	public function getUID(): string {
		return $this->uid;
	}

	/**
	 * Get the login name used when generating the token
	 *
	 * @return string
	 */
	public function getLoginName(): string {
		return parent::getLoginName();
	}

	/**
	 * Get the (encrypted) login password
	 */
	public function getPassword(): ?string {
		return parent::getPassword();
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'lastActivity' => $this->lastActivity,
			'type' => $this->type,
			'scope' => $this->getScopeAsArray()
		];
	}

	/**
	 * Get the timestamp of the last password check
	 *
	 * @return int
	 */
	public function getLastCheck(): int {
		return parent::getLastCheck();
	}

	/**
	 * Get the timestamp of the last password check
	 */
	public function setLastCheck(int $time): void {
		parent::setLastCheck($time);
	}

	public function getScope(): string {
		$scope = parent::getScope();
		if ($scope === null) {
			return '';
		}

		return $scope;
	}

	public function getScopeAsArray(): array {
		$scope = json_decode($this->getScope(), true);
		if (!$scope) {
			return [
				IToken::SCOPE_FILESYSTEM => true
			];
		}
		return $scope;
	}

	public function setScope(array|string|null $scope): void {
		if (is_array($scope)) {
			parent::setScope(json_encode($scope));
		} else {
			parent::setScope((string)$scope);
		}
	}

	public function getName(): string {
		return parent::getName();
	}

	public function setName(string $name): void {
		parent::setName($name);
	}

	public function getRemember(): int {
		return parent::getRemember();
	}

	public function setToken(string $token): void {
		parent::setToken($token);
	}

	public function setPassword(?string $password = null): void {
		parent::setPassword($password);
	}

	public function setExpires($expires): void {
		parent::setExpires($expires);
	}

	/**
	 * @return int|null
	 */
	public function getExpires() {
		return parent::getExpires();
	}

	public function setPasswordInvalid(bool $invalid) {
		parent::setPasswordInvalid($invalid);
	}

	public function wipe(): void {
		parent::setType(IToken::WIPE_TOKEN);
	}
}
