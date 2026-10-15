<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\SystemTag;

use Psr\Container\ContainerInterface;

/**
 * Interface ISystemTagManagerFactory
 *
 * Factory interface for system tag managers
 *
 * @since 9.0.0
 * @deprecated 33.0.0 use Dependency Injection instead, or \OCP\Server::get
 */
interface ISystemTagManagerFactory {
	/**
	 * Constructor for the system tag manager factory
	 *
	 * @param ContainerInterface $serverContainer server container
	 * @since 9.0.0
	 * @deprecated 33.0.0
	 */
	public function __construct(ContainerInterface $serverContainer);

	/**
	 * creates and returns an instance of the system tag manager
	 *
	 * @return ISystemTagManager
	 * @since 9.0.0
	 * @deprecated 33.0.0
	 */
	public function getManager(): ISystemTagManager;

	/**
	 * creates and returns an instance of the system tag object
	 * mapper
	 *
	 * @return ISystemTagObjectMapper
	 * @since 9.0.0
	 * @deprecated 33.0.0
	 */
	public function getObjectMapper(): ISystemTagObjectMapper;
}
