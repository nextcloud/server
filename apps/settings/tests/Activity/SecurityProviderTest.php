<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\Activity\SecurityProvider;
use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityProviderTest extends TestCase {

	/** @var IFactory|MockObject */
	private $l10n;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IManager|MockObject */
	private $activityManager;

	/** @var SecurityProvider */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->activityManager = $this->createMock(IManager::class);

		$this->provider = new SecurityProvider($this->l10n, $this->urlGenerator, $this->activityManager);
	}

	public function testParseUnrelated(): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('getType')
			->willReturn('comments');
		$this->expectException(UnknownActivityException::class);

		$this->provider->parse($lang, $event);
	}

	public function subjectData() {
		return [
			['twofactor_success'],
			['twofactor_failed'],
		];
	}

	/**
	 * @dataProvider subjectData
	 */
	public function testParse($subject): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$l = $this->createMock(IL10N::class);

		$event->expects($this->once())
			->method('getType')
			->willReturn('security');
		$this->l10n->expects($this->once())
			->method('get')
			->with('settings', $lang)
			->willReturn($l);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/password.svg')
			->willReturn('path/to/image');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('path/to/image')
			->willReturn('absolute/path/to/image');
		$event->expects($this->once())
			->method('setIcon')
			->with('absolute/path/to/image');
		$event->expects($this->once())
			->method('getSubject')
			->willReturn($subject);
		$event->method('getSubjectParameters')
			->willReturn([
				'provider' => 'myProvider',
			]);
		$event->expects($this->once())
			->method('setParsedSubject');

		$this->provider->parse($lang, $event);
	}

	public function testParseInvalidSubject(): void {
		$lang = 'ru';
		$l = $this->createMock(IL10N::class);
		$event = $this->createMock(IEvent::class);

		$event->expects($this->once())
			->method('getType')
			->willReturn('security');
		$this->l10n->expects($this->once())
			->method('get')
			->with('settings', $lang)
			->willReturn($l);
		$event->expects($this->once())
			->method('getSubject')
			->willReturn('unrelated');

		$this->expectException(UnknownActivityException::class);
		$this->provider->parse($lang, $event);
	}
}
