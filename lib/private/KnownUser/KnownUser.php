<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\KnownUser;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setKnownTo(string $knownTo)
 * @method string getKnownTo()
 * @method void setKnownUser(string $knownUser)
 * @method string getKnownUser()
 */
class KnownUser extends Entity {
	/** @var string */
	protected $knownTo;

	/** @var string */
	protected $knownUser;

	public function __construct() {
		$this->addType('knownTo', 'string');
		$this->addType('knownUser', 'string');
	}
}
