<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Comments;

use OCP\Comments\ICommentsManagerFactory;
use OCP\IServerContainer;

class ManagerFactory implements ICommentsManagerFactory {
	/**
	 * Server container
	 *
	 * @var IServerContainer
	 */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	public function getManager() {
		return $this->serverContainer->get(Manager::class);
	}
}
