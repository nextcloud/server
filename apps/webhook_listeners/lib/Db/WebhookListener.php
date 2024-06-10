<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OCP\AppFramework\Db\Entity;
use OCP\Security\ICrypto;

/**
 * @method void setUserId(string $userId)
 * @method string getUserId()
 * @method ?array getHeaders()
 */
class WebhookListener extends Entity implements \JsonSerializable {
	/** @var ?string id of the app_api application who added the webhook listener */
	protected $appId;

	/** @var string id of the user who added the webhook listener */
	protected $userId;

	/** @var string */
	protected $httpMethod;

	/** @var string */
	protected $uri;

	/** @var string */
	protected $event;

	/** @var array */
	protected $eventFilter;

	/** @var ?array */
	protected $headers;

	/** @var ?string */
	protected $authMethod;

	/** @var ?string */
	protected $authData;

	private ICrypto $crypto;

	public function __construct(
		?ICrypto $crypto = null,
	) {
		if ($crypto === null) {
			$crypto = \OCP\Server::get(ICrypto::class);
		}
		$this->crypto = $crypto;
		$this->addType('appId', 'string');
		$this->addType('userId', 'string');
		$this->addType('httpMethod', 'string');
		$this->addType('uri', 'string');
		$this->addType('event', 'string');
		$this->addType('eventFilter', 'json');
		$this->addType('headers', 'json');
		$this->addType('authMethod', 'string');
		$this->addType('authData', 'string');
	}

	public function getAuthMethodEnum(): AuthMethod {
		return AuthMethod::from(parent::getAuthMethod());
	}

	public function getAuthDataClear(): array {
		if ($this->authData === null) {
			return [];
		}
		return json_decode($this->crypto->decrypt($this->getAuthData()), associative:true, flags:JSON_THROW_ON_ERROR);
	}

	public function setAuthDataClear(
		#[\SensitiveParameter]
		?array $data
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
}
