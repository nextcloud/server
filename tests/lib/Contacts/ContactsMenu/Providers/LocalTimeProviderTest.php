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
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LocalTimeProviderTest extends TestCase {

	private IActionFactory&MockObject $actionFactory;
	private IL10N&MockObject $l;
	private IL10NFactory&MockObject $l10nFactory;
	private IURLGenerator&MockObject $urlGenerator;
	private IUserManager&MockObject $userManager;
	private ITimeFactory&MockObject $timeFactory;
	private IUserSession&MockObject $userSession;
	private IDateTimeFormatter&MockObject $dateTimeFormatter;
	private IConfig&MockObject $config;

	private LocalTimeProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->actionFactory = $this->createMock(IActionFactory::class);
		$this->l10nFactory = $this->createMock(IL10NFactory::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->l->expects($this->any())
			->method('n')
			->willReturnCallback(function ($text, $textPlural, $n, $parameters = []) {
				$formatted = str_replace('%n', (string)$n, $n === 1 ? $text : $textPlural);
				return vsprintf($formatted, $parameters);
			});
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->dateTimeFormatter = $this->createMock(IDateTimeFormatter::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->provider = new LocalTimeProvider(
			$this->actionFactory,
			$this->l10nFactory,
			$this->urlGenerator,
			$this->userManager,
			$this->timeFactory,
			$this->dateTimeFormatter,
			$this->config,
			$this->userSession,
		);
	}

	public static function dataTestProcess(): array {
		return [
			'no current user' => [
				false,
				null,
				null,
				'Local time: 10:24',
			],
			'both UTC' => [
				true,
				null,
				null,
				'10:24 • same time',
			],
			'both same time zone' => [
				true,
				'Europe/Berlin',
				'Europe/Berlin',
				'11:24 • same time',
			],
			'1h behind' => [
				true,
				'Europe/Berlin',
				'Europe/London',
				'10:24 • 1h behind',
			],
			'4:45h ahead' => [
				true,
				'Europe/Berlin',
				'Asia/Kathmandu',
				'16:09 • 4h45m ahead',
			],
		];
	}

	/**
	 * @dataProvider dataTestProcess
	 */
	public function testProcess(bool $hasCurrentUser, ?string $currentUserTZ, ?string $targetUserTZ, string $expected): void {
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

		$this->config->method('getSystemValueString')
			->with('default_timezone', 'UTC')
			->willReturn('UTC');
		$this->config
			->method('getUserValue')
			->willReturnMap([
				['user1', 'core', 'timezone', '', $targetUserTZ],
				['currentUser', 'core', 'timezone', '', $currentUserTZ],
			]);

		if ($hasCurrentUser) {
			$currentUser = $this->createMock(IUser::class);
			$currentUser->method('getUID')
				->willReturn('currentUser');
			$this->userSession->method('getUser')
				->willReturn($currentUser);
		}

		$this->timeFactory->method('getDateTime')
			->willReturnCallback(fn ($time, $tz) => (new \DateTime('2023-01-04 10:24:43', new \DateTimeZone('UTC')))->setTimezone($tz));

		$this->dateTimeFormatter->method('formatTime')
			->willReturnCallback(fn (\DateTime $time) => $time->format('H:i'));

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
				$expected,
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
