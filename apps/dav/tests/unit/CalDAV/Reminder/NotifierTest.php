<?php
/**
 * @copyright Copyright (c) 2019 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
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

namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\Reminder\Notifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use Test\TestCase;

class NotifierTest extends TestCase {
	/** @var Notifier */
	protected $notifier;

	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $factory;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	protected $l;

	protected function setUp() {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
		$this->l->expects($this->any())
			->method('n')
			->willReturnCallback(function($textSingular, $textPlural, $count, $args) {
				$text = $count === 1 ? $textSingular : $textPlural;
				$text = str_replace('%n', (string)$count, $text);
				return vsprintf($text, $args);
			});
		$this->factory = $this->createMock(IFactory::class);
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l);

		$this->notifier = new Notifier(
			$this->factory,
			$this->urlGenerator
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Notification not from this app
	 */
	public function testPrepareWrongApp(): void
	{
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects($this->never())
			->method('getSubject');

		$this->notifier->prepare($notification, 'en');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Unknown subject
	 */
	public function testPrepareWrongSubject() {
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn('wrong subject');

		$this->notifier->prepare($notification, 'en');
	}

	public function dataPrepare(): array
	{
		return [
			[
				'calendar_reminder',
				[
					'title' => 'foo',
					'start' => time() - 60 * 60 * 24
				],
				'foo (one day ago)',
				[
					'when' => 'foo',
					'description' => 'bar',
					'location' => 'NC Headquarters',
					'calendar' => 'Personal'
				],
				'Calendar: Personal<br>Date: foo<br>Description: bar<br>Where: NC Headquarters'
			],
		];
	}

	/**
	 * @dataProvider dataPrepare
	 *
	 * @param string $subjectType
	 * @param array $subjectParams
	 * @param string $subject
	 * @param array $messageParams
	 * @param string $message
	 * @throws \Exception
	 */
	public function testPrepare(string $subjectType, array $subjectParams, string $subject, array $messageParams, string $message): void
	{
		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getSubject')
			->willReturn($subjectType);
		$notification->expects($this->once())
			->method('getSubjectParameters')
			->willReturn($subjectParams);
		$notification->expects($this->once())
			->method('getMessageParameters')
			->willReturn($messageParams);

		$notification->expects($this->once())
			->method('setParsedSubject')
			->with($subject)
			->willReturnSelf();

		$notification->expects($this->once())
			->method('setParsedMessage')
			->with($message)
			->willReturnSelf();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'places/calendar.svg')
			->willReturn('icon-url');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('icon-url')
			->willReturn('absolute-icon-url');
		$notification->expects($this->once())
			->method('setIcon')
			->with('absolute-icon-url')
			->willReturnSelf();

		$return = $this->notifier->prepare($notification, 'en');

		$this->assertEquals($notification, $return);
	}
}
