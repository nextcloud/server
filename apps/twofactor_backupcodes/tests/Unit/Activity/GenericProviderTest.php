<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Two-factor backup codes
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactorBackupCodes\Test\Unit\Activity;

use InvalidArgumentException;
use OCA\TwoFactorBackupCodes\Activity\GenericProvider;
use OCP\Activity\IEvent;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class GenericProviderTest extends TestCase {

	/** @var IL10N|PHPUnit_Framework_MockObject_MockObject */
	private $l10n;

	/** @var IURLGenerator|PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	/** @var ILogger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var GenericProvider */
	private $provider;

	protected function setUp() {
		parent::setUp();

		$this->l10n = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->provider = new GenericProvider($this->l10n, $this->urlGenerator, $this->logger);
	}

	public function testParseUnrelated() {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('getType')
			->willReturn('comments');
		$this->setExpectedException(InvalidArgumentException::class);

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
	public function testParse($subject) {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$l = $this->createMock(IL10N::class);

		$event->expects($this->once())
			->method('getType')
			->willReturn('twofactor');
		$this->l10n->expects($this->once())
			->method('get')
			->with('core', $lang)
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
		$event->expects($this->once())
			->method('setParsedSubject');

		$this->provider->parse($lang, $event);
	}

	public function testParseInvalidSubject() {
		$lang = 'ru';
		$l = $this->createMock(IL10N::class);
		$event = $this->createMock(IEvent::class);

		$event->expects($this->once())
			->method('getType')
			->willReturn('twofactor');
		$this->l10n->expects($this->once())
			->method('get')
			->with('core', $lang)
			->willReturn($l);
		$event->expects($this->once())
			->method('getSubject')
			->willReturn('unrelated');

		$this->expectException(InvalidArgumentException::class);
		$this->provider->parse($lang, $event);
	}

}
