<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework;

use OCP\IContainer;
use Psr\Container\ContainerInterface;

/**
 * This is a tagging interface for a container that belongs to an app
 *
 * The interface currently extends IContainer, but this interface is deprecated as of Nextcloud 20,
 * thus this interface won't extend it anymore once that was removed. So migrate to the ContainerInterface
 * only.
 *
 * @deprecated 20.0.0
 * @since 6.0.0
 */
interface IAppContainer extends ContainerInterface, IContainer {
	/**
	 * used to return the appname of the set application
	 * @return string the name of your application
	 * @since 6.0.0
	 * @deprecated 20.0.0
	 */
	public function getAppName();

	/**
	 * @return \OCP\IServerContainer
	 * @since 6.0.0
	 * @deprecated 20.0.0
	 */
	public function getServer();

	/**
	 * @param string $middleWare
	 * @return boolean
	 * @since 6.0.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerMiddleware
	 */
	public function registerMiddleWare($middleWare);

	/**
	 * Register a capability
	 *
	 * @param string $serviceName e.g. 'OCA\Files\Capabilities'
	 * @since 8.2.0
	 * @deprecated 20.0.0 use \OCP\AppFramework\Bootstrap\IRegistrationContext::registerCapability
	 */
	public function registerCapability($serviceName);
}
