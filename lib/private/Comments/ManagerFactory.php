<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Comments;

use OCP\Comments\ICommentsManager;
use OCP\Comments\ICommentsManagerFactory;
use OCP\IServerContainer;

class ManagerFactory implements ICommentsManagerFactory {
	/**
	 * Constructor for the comments manager factory
	 *
	 * @param IServerContainer $serverContainer server container
	 */
	public function __construct(
		/**
		 * Server container
		 */
		private IServerContainer $serverContainer,
	) {
	}

	/**
	 * creates and returns an instance of the ICommentsManager
	 *
	 * @return ICommentsManager
	 * @since 9.0.0
	 */
	public function getManager() {
		return $this->serverContainer->get(Manager::class);
	}
}
