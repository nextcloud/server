<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setActorUid(string $uid)
 * @method string|null getActorUid()
 * @method void setUid(string $uid)
 * @method string|null getUid()
 * @method void setEmail(string $email)
 * @method string|null getEmail()
 * @method void setFederatedCloudId(string $federatedCloudId)
 * @method string|null getFederatedCloudId()
 * @method void setCard(string $card)
 * @method string getCard()
 * @method void setLastContact(int $lastContact)
 * @method int getLastContact()
 */
class RecentContact extends Entity {
	protected string $actorUid = '';
	protected ?string $uid = null;
	protected ?string $email = null;
	protected ?string $federatedCloudId = null;
	protected string $card = '';
	protected int $lastContact = -1;

	public function __construct() {
		$this->addType('actorUid', 'string');
		$this->addType('uid', 'string');
		$this->addType('email', 'string');
		$this->addType('federatedCloudId', 'string');
		$this->addType('card', 'blob');
		$this->addType('lastContact', 'int');
	}
}
