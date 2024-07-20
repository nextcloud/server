<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Contacts\ContactsMenu\Providers;

use OC\Contacts\ContactsMenu\Providers\LocalTimeProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\ILinkAction;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory as IL10NFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LocalTimeProviderTest extends TestCase {
	/** @var IActionFactory|MockObject */
	private $actionFactory;
	/** @var IL10N|MockObject */
	private $l;
	/** @var IL10NFactory|MockObject */
	private $l10nFactory;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var IUserManager|MockObject */
	private $userManager;
	/** @var ITimeFactory|MockObject */
	private $timeFactory;
	/** @var IDateTimeFormatter|MockObject */
	private $dateTimeFormatter;
	/** @var IConfig|MockObject */
	private $config;

	private LocalTimeProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->actionFactory = $this->createMock(IActionFactory::class);
		$this->l10nFactory = $this->createMock(IL10NFactory::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->will($this->returnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->dateTimeFormatter = $this->createMock(IDateTimeFormatter::class);
		$this->config = $this->createMock(IConfig::class);

		$this->provider = new LocalTimeProvider(
			$this->actionFactory,
			$this->l10nFactory,
			$this->urlGenerator,
			$this->userManager,
			$this->timeFactory,
			$this->dateTimeFormatter,
			$this->config
		);
	}

	public function testProcess(): void {
		$entry = $this->createMock(IEntry::class);
		$entry->expects($this->once())
			->method('getProperty')
			->with('UID')
			->willReturn('user1');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user1');
		$this->userManager->expects($this->once())
			->method('get')
			->with('user1')
			->willReturn($user);

		$this->l10nFactory->method('get')
			->with('lib')
			->willReturn($this->l);

		$this->config->method('getUserValue')
			->with('user1', 'core', 'timezone')
			->willReturn('America/Los_Angeles');

		$now = new \DateTime('2023-01-04 10:24:43');
		$this->timeFactory->method('getDateTime')
			->willReturn($now);

		$now = new \DateTime('2023-01-04 10:24:43');
		$this->dateTimeFormatter->method('formatTime')
			->with($now, 'short', $this->anything())
			->willReturn('01:24');

		$this->urlGenerator->method('imagePath')
			->willReturn('actions/recent.svg');
		$this->urlGenerator->method('getAbsoluteURL')
			->with('actions/recent.svg')
			->willReturn('https://localhost/actions/recent.svg');

		$action = $this->createMock(ILinkAction::class);
		$this->actionFactory->expects($this->once())
			->method('newLinkAction')
			->with(
				'https://localhost/actions/recent.svg',
				'Local time: 01:24',
				'#',
				'timezone'
			)
			->willReturn($action);

		$entry->expects($this->once())
			->method('addAction')
			->with($action);

		$this->provider->process($entry);
	}

	public function testProcessNoUser(): void {
		$entry = $this->createMock(IEntry::class);
		$entry->expects($this->once())
			->method('getProperty')
			->with('UID')
			->willReturn('user1');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn(null);

		$entry->expects($this->never())
			->method('addAction');

		$this->provider->process($entry);
	}
}
