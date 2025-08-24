<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use Exception;
use OCP\Files\IHomeStorage;
use OCP\IUser;

class HomeObjectStoreStorage extends ObjectStoreStorage implements IHomeStorage {
	protected IUser $user;

	/**
	 * The home user storage requires a user object to create a unique storage id
	 *
	 * @param array $parameters
	 * @throws Exception
	 */
	public function __construct(array $parameters) {
		if (! isset($parameters['user']) || ! $parameters['user'] instanceof IUser) {
			throw new Exception('missing user object in parameters');
		}
		$this->user = $parameters['user'];
		parent::__construct($parameters);
	}

	public function getId(): string {
		return 'object::user:' . $this->user->getUID();
	}

	public function getOwner(string $path): string|false {
		return $this->user->getUID();
	}

	public function getUser(): IUser {
		return $this->user;
	}
}
