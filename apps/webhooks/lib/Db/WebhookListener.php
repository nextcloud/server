<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setUserId(string $userId)
 * @method string getUserId()
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

	/** @var ?string */
	protected $headers;

	/** @var ?string */
	protected $authMethod;

	/** @var ?string */
	protected $authData;

	public function __construct() {
		$this->addType('appId', 'string');
		$this->addType('userId', 'string');
		$this->addType('httpMethod', 'string');
		$this->addType('uri', 'string');
		$this->addType('event', 'string');
		$this->addType('eventFilter', 'json');
		$this->addType('headers', 'json');
		$this->addType('authMethod', 'string');
		$this->addType('authData', 'json');
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
