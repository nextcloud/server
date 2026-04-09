<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

use OCP\IServerContainer;

/**
 * Interface ICommentsManagerFactory
 *
 * This class is responsible for instantiating and returning an ICommentsManager
 * instance.
 *
 * @since 9.0.0
 */
interface ICommentsManagerFactory {
	/**
	 * Constructor for the comments manager factory
	 *
	 * @param IServerContainer $serverContainer server container
	 * @since 9.0.0
	 */
	public function __construct(IServerContainer $serverContainer);

	/**
	 * creates and returns an instance of the ICommentsManager
	 *
	 * @return ICommentsManager
	 * @since 9.0.0
	 */
	public function getManager();
}
