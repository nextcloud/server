<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Share20;

use OC\EventDispatcher\EventDispatcher;
use OC\Share20\LegacyHooks;
use OC\Share20\Manager;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\File;
use OCP\IServerContainer;
use OCP\Server;
use OCP\Share\Events\BeforeShareCreatedEvent;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class Dummy {
	public function postShare() {
	}
	public function preShare() {
	}
	public function postFromSelf() {
	}
	public function post() {
	}
	public function pre() {
	}
}

class LegacyHooksTest extends TestCase {
	/** @var LegacyHooks */
	private $hooks;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$symfonyDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		$logger = $this->createMock(LoggerInterface::class);
		$this->eventDispatcher = new EventDispatcher($symfonyDispatcher, Server::get(IServerContainer::class), $logger);
		$this->hooks = new LegacyHooks($this->eventDispatcher);
		$this->manager = Server::get(IShareManager::class);
	}

	public function testPreUnshare(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['pre'])->getMock();
		Util::connectHook('OCP\Share', 'pre_unshare', $hookListner, 'pre');

		$hookListnerExpectsPre = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
		];

		$hookListner
			->expects($this->exactly(1))
			->method('pre')
			->with($hookListnerExpectsPre);

		$event = new BeforeShareDeletedEvent($share);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function testPostUnshare(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['post'])->getMock();
		Util::connectHook('OCP\Share', 'post_unshare', $hookListner, 'post');

		$hookListnerExpectsPost = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
			'deletedShares' => [
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'awesomeUser',
					'itemparent' => null,
					'uidOwner' => 'sharedBy',
					'fileSource' => 1,
					'fileTarget' => 'myTarget',
				],
			],
		];

		$hookListner
			->expects($this->exactly(1))
			->method('post')
			->with($hookListnerExpectsPost);

		$event = new ShareDeletedEvent($share);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function testPostUnshareFromSelf(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$info = $this->createMock(ICacheEntry::class);
		$info->method('getMimeType')->willReturn('text/plain');

		$share = $this->manager->newShare();
		$share->setId(42)
			->setProviderId('prov')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setNodeCacheEntry($info);

		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['postFromSelf'])->getMock();
		Util::connectHook('OCP\Share', 'post_unshareFromSelf', $hookListner, 'postFromSelf');

		$hookListnerExpectsPostFromSelf = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_USER,
			'shareWith' => 'awesomeUser',
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
			'itemTarget' => 'myTarget',
			'unsharedItems' => [
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => IShare::TYPE_USER,
					'shareWith' => 'awesomeUser',
					'itemparent' => null,
					'uidOwner' => 'sharedBy',
					'fileSource' => 1,
					'fileTarget' => 'myTarget',
					'itemTarget' => 'myTarget',
				],
			],
		];

		$hookListner
			->expects($this->exactly(1))
			->method('postFromSelf')
			->with($hookListnerExpectsPostFromSelf);

		$event = new ShareDeletedFromSelfEvent($share);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function testPreShare(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['preShare'])->getMock();
		Util::connectHook('OCP\Share', 'pre_shared', $hookListner, 'preShare');

		$run = true;
		$error = '';

		$expected = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'run' => &$run,
			'error' => &$error,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('preShare')
			->with($expected);

		$event = new BeforeShareCreatedEvent($share);
		$this->eventDispatcher->dispatchTyped($event);
	}

	public function testPreShareError(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['preShare'])->getMock();
		Util::connectHook('OCP\Share', 'pre_shared', $hookListner, 'preShare');

		$run = true;
		$error = '';

		$expected = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'run' => &$run,
			'error' => &$error,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('preShare')
			->with($expected)
			->willReturnCallback(function ($data): void {
				$data['run'] = false;
				$data['error'] = 'I error';
			});

		$event = new BeforeShareCreatedEvent($share);
		$this->eventDispatcher->dispatchTyped($event);

		$this->assertTrue($event->isPropagationStopped());
		$this->assertSame('I error', $event->getError());
	}

	public function testPostShare(): void {
		$path = $this->createMock(File::class);
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = $this->manager->newShare();
		$share->setId(42)
			->setShareType(IShare::TYPE_LINK)
			->setSharedWith('awesomeUser')
			->setSharedBy('sharedBy')
			->setNode($path)
			->setTarget('myTarget')
			->setPermissions(Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password')
			->setToken('token');


		$hookListner = $this->getMockBuilder(Dummy::class)->onlyMethods(['postShare'])->getMock();
		Util::connectHook('OCP\Share', 'post_shared', $hookListner, 'postShare');

		$expected = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => IShare::TYPE_LINK,
			'shareWith' => 'awesomeUser',
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'itemTarget' => 'myTarget',
			'fileTarget' => 'myTarget',
			'permissions' => Constants::PERMISSION_ALL,
			'expiration' => $date,
			'token' => 'token',
			'path' => null,
		];

		$hookListner
			->expects($this->exactly(1))
			->method('postShare')
			->with($expected);

		$event = new ShareCreatedEvent($share);
		$this->eventDispatcher->dispatchTyped($event);
	}
}
