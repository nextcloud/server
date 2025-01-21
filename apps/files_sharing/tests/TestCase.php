<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Cache\Storage;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\Group\Database;
use OC\User\DisplayNameCache;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\External\MountProvider as ExternalMountProvider;
use OCA\Files_Sharing\MountProvider;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\Share\IShare;
use Test\Traits\MountProviderTrait;

/**
 * Class TestCase
 *
 * @group DB
 *
 * Base class for sharing tests.
 */
abstract class TestCase extends \Test\TestCase {
	use MountProviderTrait;

	public const TEST_FILES_SHARING_API_USER1 = 'test-share-user1';
	public const TEST_FILES_SHARING_API_USER2 = 'test-share-user2';
	public const TEST_FILES_SHARING_API_USER3 = 'test-share-user3';
	public const TEST_FILES_SHARING_API_USER4 = 'test-share-user4';

	public const TEST_FILES_SHARING_API_GROUP1 = 'test-share-group1';

	public $filename;
	public $data;
	/**
	 * @var View
	 */
	public $view;
	/**
	 * @var View
	 */
	public $view2;
	public $folder;
	public $subfolder;

	/** @var \OCP\Share\IManager */
	protected $shareManager;
	/** @var IRootFolder */
	protected $rootFolder;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$app = new Application();
		$app->registerMountProviders(
			\OC::$server->get(IMountProviderCollection::class),
			\OC::$server->get(MountProvider::class),
			\OC::$server->get(ExternalMountProvider::class),
		);

		// reset backend
		\OC_User::clearBackends();
		\OC::$server->getGroupManager()->clearBackends();

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks(\OC::$server->getSystemConfig());

		// create users
		$backend = new \Test\Util\User\Dummy();
		\OC_User::useBackend($backend);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER3);
		$backend->createUser(self::TEST_FILES_SHARING_API_USER4, self::TEST_FILES_SHARING_API_USER4);

		// create group
		$groupBackend = new \Test\Util\Group\Dummy();
		$groupBackend->createGroup(self::TEST_FILES_SHARING_API_GROUP1);
		$groupBackend->createGroup('group');
		$groupBackend->createGroup('group1');
		$groupBackend->createGroup('group2');
		$groupBackend->createGroup('group3');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER1, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER2, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER3, 'group');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER2, 'group1');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER3, 'group2');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER4, 'group3');
		$groupBackend->addToGroup(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_GROUP1);
		\OC::$server->getGroupManager()->addBackend($groupBackend);
	}

	protected function setUp(): void {
		parent::setUp();
		\OC::$server->get(DisplayNameCache::class)->clear();

		//login as user1
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->data = 'foobar';
		$this->view = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$this->view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		$this->shareManager = \OC::$server->getShareManager();
		$this->rootFolder = \OC::$server->getRootFolder();
	}

	protected function tearDown(): void {
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->delete('share');
		$qb->execute();

		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->delete('mounts');
		$qb->execute();

		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->delete('filecache')->runAcrossAllShards();
		$qb->execute();

		parent::tearDown();
	}

	public static function tearDownAfterClass(): void {
		// cleanup users
		$user = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER1);
		if ($user !== null) {
			$user->delete();
		}
		$user = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER2);
		if ($user !== null) {
			$user->delete();
		}
		$user = \OC::$server->getUserManager()->get(self::TEST_FILES_SHARING_API_USER3);
		if ($user !== null) {
			$user->delete();
		}

		// delete group
		$group = \OC::$server->getGroupManager()->get(self::TEST_FILES_SHARING_API_GROUP1);
		if ($group) {
			$group->delete();
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		Filesystem::tearDown();

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');
		\OC::$server->getGroupManager()->clearBackends();
		\OC::$server->getGroupManager()->addBackend(new Database());

		parent::tearDownAfterClass();
	}

	/**
	 * @param string $user
	 * @param bool $create
	 * @param bool $password
	 */
	protected static function loginHelper($user, $create = false, $password = false) {
		if ($password === false) {
			$password = $user;
		}

		if ($create) {
			$userManager = \OC::$server->getUserManager();
			$groupManager = \OC::$server->getGroupManager();

			$userObject = $userManager->createUser($user, $password);
			$group = $groupManager->createGroup('group');

			if ($group && $userObject) {
				$group->addUser($userObject);
			}
		}

		\OC_Util::tearDownFS();
		Storage::getGlobalCache()->clearCache();
		\OC::$server->getUserSession()->setUser(null);
		Filesystem::tearDown();
		\OC::$server->getUserSession()->login($user, $password);
		\OC::$server->getUserFolder($user);

		\OC_Util::setupFS($user);
	}

	/**
	 * get some information from a given share
	 * @param int $shareID
	 * @return array with: item_source, share_type, share_with, item_type, permissions
	 */
	protected function getShareFromId($shareID) {
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->select('item_source', '`share_type', 'share_with', 'item_type', 'permissions')
			->from('share')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($shareID))
			);
		$result = $qb->execute();
		$share = $result->fetch();
		$result->closeCursor();

		return $share;
	}

	/**
	 * @param int $type The share type
	 * @param string $path The path to share relative to $initiators root
	 * @param string $initiator
	 * @param string $recipient
	 * @param int $permissions
	 * @return IShare
	 */
	protected function share($type, $path, $initiator, $recipient, $permissions) {
		$userFolder = $this->rootFolder->getUserFolder($initiator);
		$node = $userFolder->get($path);

		$share = $this->shareManager->newShare();
		$share->setShareType($type)
			->setSharedWith($recipient)
			->setSharedBy($initiator)
			->setNode($node)
			->setPermissions($permissions);
		$share = $this->shareManager->createShare($share);
		$share->setStatus(IShare::STATUS_ACCEPTED);
		$share = $this->shareManager->updateShare($share);

		return $share;
	}
}
