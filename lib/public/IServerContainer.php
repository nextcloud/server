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
	 * The contacts manager will act as a broker between consumers for contacts information and
	 * providers which actual deliver the contact information.
	 *
	 * @return \OCP\Contacts\IManager
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getContactsManager();

	/**
	 * The current request object holding all information about the request currently being processed
	 * is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getRequest();

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return \OCP\Files\IRootFolder
	 * @since 6.0.0 - between 6.0.0 and 8.0.0 this returned \OCP\Files\Folder
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getRootFolder();

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder
	 * @since 6.0.0 - parameter $userId was added in 8.0.0
	 * @see getUserFolder in \OCP\Files\IRootFolder
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getUserFolder($userId = null);

	/**
	 * Returns a user manager
	 *
	 * @return \OCP\IUserManager
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getUserManager();

	/**
	 * Returns a group manager
	 *
	 * @return \OCP\IGroupManager
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getGroupManager();

	/**
	 * Returns the user session
	 *
	 * @return \OCP\IUserSession
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getUserSession();

	/**
	 * Returns the config manager
	 *
	 * @return \OCP\IConfig
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getConfig();

	/**
	 * Returns a Crypto instance
	 *
	 * @return \OCP\Security\ICrypto
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getCrypto();

	/**
	 * Returns a Hasher instance
	 *
	 * @return \OCP\Security\IHasher
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getHasher();

	/**
	 * Returns a SecureRandom instance
	 *
	 * @return \OCP\Security\ISecureRandom
	 * @since 8.1.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getSecureRandom();

	/**
	 * @return \OCP\L10N\IFactory
	 * @since 8.2.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getL10NFactory();

	/**
	 * get an L10N instance
	 * @param string $app appid
	 * @param string $lang
	 * @return \OCP\IL10N
	 * @since 6.0.0 - parameter $lang was added in 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getL10N($app, $lang = null);

	/**
	 * @return \OC\Encryption\Manager
	 * @since 8.1.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getEncryptionManager();

	/**
	 * @return \OC\Encryption\File
	 * @since 8.1.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getEncryptionFilesHelper();

	/**
	 * Returns the URL generator
	 *
	 * @return \OCP\IURLGenerator
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getURLGenerator();

	/**
	 * Returns an ICache instance
	 *
	 * @return \OCP\ICache
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getCache();

	/**
	 * Returns an \OCP\CacheFactory instance
	 *
	 * @return \OCP\ICacheFactory
	 * @since 7.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getMemCacheFactory();

	/**
	 * Returns the current session
	 *
	 * @return \OCP\ISession
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getSession();

	/**
	 * Returns the activity manager
	 *
	 * @return \OCP\Activity\IManager
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getActivityManager();

	/**
	 * Returns the current session
	 *
	 * @return \OCP\IDBConnection
	 * @since 6.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getDatabaseConnection();

	/**
	 * Returns an job list for controlling background jobs
	 *
	 * @return \OCP\BackgroundJob\IJobList
	 * @since 7.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getJobList();

	/**
	 * Get the certificate manager
	 *
	 * @return \OCP\ICertificateManager
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getCertificateManager();

	/**
	 * Get the manager for temporary files and folders
	 *
	 * @return \OCP\ITempManager
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getTempManager();

	/**
	 * Get the app manager
	 *
	 * @return \OCP\App\IAppManager
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getAppManager();

	/**
	 * Get the webroot
	 *
	 * @return string
	 * @since 8.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getWebRoot();

	/**
	 * Creates a new mailer
	 *
	 * @return \OCP\Mail\IMailer
	 * @since 8.1.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getMailer();

	/**
	 * Get the locking provider
	 *
	 * @return \OCP\Lock\ILockingProvider
	 * @since 8.1.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getLockingProvider();

	/**
	 * Get the MimeTypeDetector
	 *
	 * @return \OCP\Files\IMimeTypeDetector
	 * @since 8.2.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getMimeTypeDetector();

	/**
	 * Get the MimeTypeLoader
	 *
	 * @return \OCP\Files\IMimeTypeLoader
	 * @since 8.2.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getMimeTypeLoader();

	/**
	 * Get the Notification Manager
	 *
	 * @return \OCP\Notification\IManager
	 * @since 9.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getNotificationManager();

	/**
	 * @return \OCP\Federation\ICloudIdManager
	 * @since 12.0.0
	 * @deprecated 20.0.0 have it injected or fetch it through \Psr\Container\ContainerInterface::get
	 */
	public function getCloudIdManager();
}
