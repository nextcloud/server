<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use OC\SystemConfig;
use OC\User\DisplayNameCache;
use OC\User\Session;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\External\MountProvider as ExternalMountProvider;
use OCA\Files_Sharing\Listener\SharesUpdatedListener;
use OCA\Files_Sharing\MountProvider;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Test\Traits\GroupTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Base class for sharing tests.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
abstract class TestCase extends \Test\TestCase {
	use MountProviderTrait;
	use UserTrait;
	use GroupTrait;

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

	/** @var IManager */
	protected $shareManager;
	/** @var IRootFolder */
	protected $rootFolder;
	protected Session $userSession;
	protected ISetupManager $setupManager;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = Server::get(IManager::class);
		$this->rootFolder = Server::get(IRootFolder::class);

		$this->setupManager = Server::get(ISetupManager::class);
		$this->userSession = Server::get(IUserSession::class);

		$app = new Application();
		$app->registerMountProviders(
			Server::get(IMountProviderCollection::class),
			Server::get(MountProvider::class),
			Server::get(ExternalMountProvider::class),
		);

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks(Server::get(SystemConfig::class));

		Server::get(SharesUpdatedListener::class)->setCutOffMarkTime(-1);

		Server::get(DisplayNameCache::class)->clear();

		$this->createUser(self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER1);
		$this->createUser(self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER2);
		$this->createUser(self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER3);
		$this->createUser(self::TEST_FILES_SHARING_API_USER4, self::TEST_FILES_SHARING_API_USER4);

		$this->createGroup(self::TEST_FILES_SHARING_API_GROUP1, [
			self::TEST_FILES_SHARING_API_USER2,
		]);
		$this->createGroup('group', [
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3,
		]);
		$this->createGroup('group1', [
			self::TEST_FILES_SHARING_API_USER2,
		]);
		$this->createGroup('group2', [
			self::TEST_FILES_SHARING_API_USER3,
		]);
		$this->createGroup('group3', [
			self::TEST_FILES_SHARING_API_USER4,
		]);

		//login as user1
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->data = 'foobar';
		$this->view = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$this->view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
	}

	protected function tearDown(): void {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->delete('share');
		$qb->executeStatement();

		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->delete('mounts');
		$qb->executeStatement();

		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->delete('filecache')->runAcrossAllShards();
		$qb->executeStatement();

		$this->userSession->setUser(null);
		$this->setupManager->tearDown();

		parent::tearDown();
	}

	protected function loginHelper(string $uid) {
		$this->setupManager->tearDown();
		$user = Server::get(IUserManager::class)->get($uid);
		$this->userSession->completeLogin($user, ['loginName' => $uid, 'password' => $uid], false);

		$this->rootFolder->newFolder('/' . $uid . '/files');
	}

	/**
	 * get some information from a given share
	 * @param int $shareID
	 * @return array with: item_source, share_type, share_with, item_type, permissions
	 */
	protected function getShareFromId($shareID) {
		$qb = Server::get(IDBConnection::class)->getQueryBuilder();
		$qb->select('item_source', '`share_type', 'share_with', 'item_type', 'permissions')
			->from('share')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($shareID))
			);
		$result = $qb->executeQuery();
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
