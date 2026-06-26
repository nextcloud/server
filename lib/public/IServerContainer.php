<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

use Psr\Container\ContainerInterface;

/**
 * This is a tagging interface for the server container
 *
 * The interface currently extends IContainer, but this interface is deprecated as of Nextcloud 20,
 * thus this interface won't extend it anymore once that was removed. So migrate to the ContainerInterface
 * only.
 *
 * @deprecated 20.0.0
 *
 * @since 6.0.0
 */
interface IServerContainer extends ContainerInterface, IContainer {

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder|null
	 * @since 6.0.0 - parameter $userId was added in 8.0.0
	 * @see getUserFolder in \OCP\Files\IRootFolder
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getUserFolder($userId = null);

	/**
	 * get an L10N instance
	 *
	 * @param string $app appid
	 * @param string $lang
	 * @return \OCP\IL10N
	 * @since 6.0.0 - parameter $lang was added in 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getL10N($app, $lang = null);

	/**
	 * Get the webroot
	 *
	 * @return string
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getWebRoot(): string;
}
