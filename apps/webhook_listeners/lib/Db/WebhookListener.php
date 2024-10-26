<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OCP\AppFramework\Db\Entity;
use OCP\Security\ICrypto;
use OCP\Server;

/**
 * @method void setUserId(?string $userId)
 * @method ?string getAppId()
 * @method ?string getUserId()
 * @method string getHttpMethod()
 * @method string getUri()
 * @method ?array getHeaders()
 * @method ?string getAuthData()
 * @method void setAuthData(?string $data)
 * @method string getAuthMethod()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class WebhookListener extends Entity implements \JsonSerializable {
	/**
	 * @var ?string id of the app_api application who added the webhook listener
	 */
	protected $appId = null;

	/**
	 * @var ?string id of the user who added the webhook listener
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $userId = null;

	/**
	 * @var string
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $httpMethod;

	/**
	 * @var string
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $uri;

	/**
	 * @var string
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $event;

	/**
	 * @var array
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $eventFilter;

	/**
	 * @var ?string
	 *              If not empty, id of the user that needs to be connected for the webhook to trigger
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $userIdFilter;

	/**
	 * @var ?array
	 */
	protected $headers = null;

	/**
	 * @var string
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $authMethod;

	/**
	 * @var ?string
	 */
	protected $authData = null;

	private ICrypto $crypto;

	public function __construct(
		?ICrypto $crypto = null,
	) {
		if ($crypto === null) {
			$crypto = Server::get(ICrypto::class);
		}
		$this->crypto = $crypto;
		$this->addType('appId', 'string');
		$this->addType('userId', 'string');
		$this->addType('httpMethod', 'string');
		$this->addType('uri', 'string');
		$this->addType('event', 'string');
		$this->addType('eventFilter', 'json');
		$this->addType('userIdFilter', 'string');
		$this->addType('headers', 'json');
		$this->addType('authMethod', 'string');
		$this->addType('authData', 'string');
	}

	public function getAuthMethodEnum(): AuthMethod {
		return AuthMethod::from($this->getAuthMethod());
	}

	public function getAuthDataClear(): array {
		$authData = $this->getAuthData();
		if ($authData === null) {
			return [];
		}
		return json_decode($this->crypto->decrypt($authData), associative:true, flags:JSON_THROW_ON_ERROR);
	}

	public function setAuthDataClear(
		#[\SensitiveParameter]
		?array $data,
	): void {
		if ($data === null) {
			if ($this->getAuthMethodEnum() === AuthMethod::Header) {
				throw new \UnexpectedValueException('Header auth method needs an associative array of headers as auth data');
			}
			$this->setAuthData(null);
			return;
		}
		$this->setAuthData($this->crypto->encrypt(json_encode($data)));
	}

	public function jsonSerialize(): array {
		$fields = array_keys($this->getFieldTypes());
		return array_combine(
			$fields,
			array_map(
				fn ($field) => $this->getter($field),
				$fields
			)
		);
	}

	public function getAppId(): ?string {
		return $this->appId;
	}
}
