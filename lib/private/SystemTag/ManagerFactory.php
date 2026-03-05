<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\SystemTag;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IServerContainer;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagManagerFactory;
use OCP\SystemTag\ISystemTagObjectMapper;

/**
 * Default factory class for system tag managers
 *
 * @package OCP\SystemTag
 * @since 9.0.0
 */
class ManagerFactory implements ISystemTagManagerFactory {
	/**
	 * Constructor for the system tag manager factory
	 */
	public function __construct(
		private IServerContainer $serverContainer,
	) {
	}

	/**
	 * Creates and returns an instance of the system tag manager
	 *
	 * @since 9.0.0
	 */
	public function getManager(): ISystemTagManager {
		return new SystemTagManager(
			$this->serverContainer->get(IDBConnection::class),
			$this->serverContainer->get(IGroupManager::class),
			$this->serverContainer->get(IEventDispatcher::class),
			$this->serverContainer->get(IUserSession::class),
			$this->serverContainer->get(IAppConfig::class),
		);
	}

	/**
	 * Creates and returns an instance of the system tag object
	 * mapper
	 *
	 * @since 9.0.0
	 */
	public function getObjectMapper(): ISystemTagObjectMapper {
		return new SystemTagObjectMapper(
			$this->serverContainer->get(IDBConnection::class),
			$this->getManager(),
			$this->serverContainer->get(IEventDispatcher::class),
		);
	}
}
