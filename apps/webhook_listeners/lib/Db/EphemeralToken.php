<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getTokenId()
 * @method ?string getUserId()
 * @method int getCreatedAt()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EphemeralToken extends Entity implements \JsonSerializable {
	/**
	 * @var int id of the token in the oc_authtoken db table
	 */
	protected $tokenId;

	/**
	 * @var ?string id of the user wich the token belongs to
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $userId = null;

	/**
	 * @var int token creation timestamp
	 * @psalm-suppress PropertyNotSetInConstructor
	 */
	protected $createdAt;

	public function __construct() {
		$this->addType('tokenId', 'integer');
		$this->addType('userId', 'string');
		$this->addType('createdAt', 'integer');
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
