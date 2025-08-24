<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Share20;

use OC\Files\Node\Node;
use OCA\ShareByMail\Settings\SettingsManager;
use OCA\ShareByMail\ShareByMailProvider;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class ShareByMailProviderTest
 *
 * @package Test\Share20
 * @group DB
 */
class ShareByMailProviderTest extends TestCase {
	/** @var IDBConnection */
	protected $dbConn;

	/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;

	/** @var IRootFolder | \PHPUnit\Framework\MockObject\MockObject */
	protected $rootFolder;

	/** @var ShareByMailProvider */
	protected $provider;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IMailer */
	protected $mailer;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IL10N */
	protected $l10n;

	/** @var \PHPUnit\Framework\MockObject\MockObject|Defaults */
	protected $defaults;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IURLGenerator */
	protected $urlGenerator;

	/** @var IConfig|MockObject */
	protected $config;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IHasher|MockObject */
	private $hasher;

	/** @var \OCP\Activity\IManager|MockObject */
	private $activityManager;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var \OCP\Share\IManager|MockObject */
	private $shareManager;

	/** @var ISecureRandom|MockObject */
	private $secureRandom;

	/** @var SettingsManager|MockObject */
	private $settingsManager;

	protected function setUp(): void {
		$this->dbConn = Server::get(IDBConnection::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaults = $this->getMockBuilder(Defaults::class)->disableOriginalConstructor()->getMock();
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->activityManager = $this->createMock(\OCP\Activity\IManager::class);
		$this->settingsManager = $this->createMock(SettingsManager::class);
		$this->hasher = $this->createMock(IHasher::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->shareManager = $this->createMock(\OCP\Share\IManager::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->config = $this->createMock(IConfig::class);

		// Empty share table
		$this->dbConn->getQueryBuilder()->delete('share')->execute();

		$this->provider = new ShareByMailProvider(
			$this->config,
			$this->dbConn,
			$this->secureRandom,
			$this->userManager,
			$this->rootFolder,
			$this->l10n,
			$this->logger,
			$this->mailer,
			$this->urlGenerator,
			$this->activityManager,
			$this->settingsManager,
			$this->defaults,
			$this->hasher,
			$this->eventDispatcher,
			$this->shareManager,
		);
	}

	protected function tearDown(): void {
		$this->dbConn->getQueryBuilder()->delete('share')->execute();
		$this->dbConn->getQueryBuilder()->delete('filecache')->runAcrossAllShards()->execute();
		$this->dbConn->getQueryBuilder()->delete('storages')->execute();
	}

	/**
	 * @param int $shareType
	 * @param string $sharedWith
	 * @param string $sharedBy
	 * @param string $shareOwner
	 * @param string $itemType
	 * @param int $fileSource
	 * @param string $fileTarget
	 * @param int $permissions
	 * @param $token
	 * @param $expiration
	 * @param $parent
	 * @return int
	 *
	 * @throws \OCP\DB\Exception
	 */
	private function addShareToDB($shareType, $sharedWith, $sharedBy, $shareOwner,
		$itemType, $fileSource, $fileTarget, $permissions, $token, $expiration,
		$parent) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share');

		if ($shareType) {
			$qb->setValue('share_type', $qb->expr()->literal($shareType));
		}
		if ($sharedWith) {
			$qb->setValue('share_with', $qb->expr()->literal($sharedWith));
		}
		if ($sharedBy) {
			$qb->setValue('uid_initiator', $qb->expr()->literal($sharedBy));
		}
		if ($shareOwner) {
			$qb->setValue('uid_owner', $qb->expr()->literal($shareOwner));
		}
		if ($itemType) {
			$qb->setValue('item_type', $qb->expr()->literal($itemType));
		}
		if ($fileSource) {
			$qb->setValue('file_source', $qb->expr()->literal($fileSource));
		}
		if ($fileTarget) {
			$qb->setValue('file_target', $qb->expr()->literal($fileTarget));
		}
		if ($permissions) {
			$qb->setValue('permissions', $qb->expr()->literal($permissions));
		}
		if ($token) {
			$qb->setValue('token', $qb->expr()->literal($token));
		}
		if ($expiration) {
			$qb->setValue('expiration', $qb->createNamedParameter($expiration, IQueryBuilder::PARAM_DATETIME_MUTABLE));
		}
		if ($parent) {
			$qb->setValue('parent', $qb->expr()->literal($parent));
		}

		$this->assertEquals(1, $qb->execute());
		return $qb->getLastInsertId();
	}

	public function testGetSharesByWithResharesAndNoNode(): void {
		$this->addShareToDB(
			IShare::TYPE_EMAIL,
			'external.one@domain.tld',
			'user1',
			'user1',
			'folder',
			42,
			null,
			17,
			'foobar',
			null,
			null,
		);
		$this->addShareToDB(
			IShare::TYPE_EMAIL,
			'external.two@domain.tld',
			'user2',
			'user2',
			'folder',
			42,
			null,
			17,
			'barfoo',
			null,
			null,
		);

		// Return own shares only if not asked for a specific node
		/** @var IShare[] $actual */
		$actual = $this->provider->getSharesBy(
			'user1',
			IShare::TYPE_EMAIL,
			null,
			true,
			-1,
			0,
		);

		$this->assertCount(1, $actual);

		$this->assertEquals(IShare::TYPE_EMAIL, $actual[0]->getShareType());
		$this->assertEquals('user1', $actual[0]->getSharedBy());
		$this->assertEquals('user1', $actual[0]->getShareOwner());
		$this->assertEquals('external.one@domain.tld', $actual[0]->getSharedWith());
	}

	public function testGetSharesByWithResharesAndNode(): void {
		$this->addShareToDB(
			IShare::TYPE_EMAIL,
			'external.one@domain.tld',
			'user1',
			'user1',
			'folder',
			42,
			null,
			17,
			'foobar',
			null,
			null,
		);
		$this->addShareToDB(
			IShare::TYPE_EMAIL,
			'external.two@domain.tld',
			'user2',
			'user2',
			'folder',
			42,
			null,
			17,
			'barfoo',
			null,
			null,
		);

		$node = $this->createMock(Node::class);
		$node->expects($this->once())
			->method('getId')
			->willReturn(42);

		// Return all shares if asked for specific node
		/** @var IShare[] $actual */
		$actual = $this->provider->getSharesBy(
			'user1',
			IShare::TYPE_EMAIL,
			$node,
			true,
			-1,
			0,
		);

		$this->assertCount(2, $actual);

		$this->assertEquals(IShare::TYPE_EMAIL, $actual[0]->getShareType());
		$this->assertEquals('user1', $actual[0]->getSharedBy());
		$this->assertEquals('user1', $actual[0]->getShareOwner());
		$this->assertEquals('external.one@domain.tld', $actual[0]->getSharedWith());

		$this->assertEquals(IShare::TYPE_EMAIL, $actual[1]->getShareType());
		$this->assertEquals('user2', $actual[1]->getSharedBy());
		$this->assertEquals('user2', $actual[1]->getShareOwner());
		$this->assertEquals('external.two@domain.tld', $actual[1]->getSharedWith());
	}
}
