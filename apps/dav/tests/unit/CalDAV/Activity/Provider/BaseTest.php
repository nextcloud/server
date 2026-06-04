<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Provider;

use OCA\DAV\CalDAV\Activity\Provider\Base;
use OCP\Activity\IEvent;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BaseTest extends TestCase {
	protected IUserManager&MockObject $userManager;
	protected IGroupManager&MockObject $groupManager;
	protected IURLGenerator&MockObject $url;
	protected Base&MockObject $provider;

	protected function setUp(): void {
		parent::setUp();
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->provider = $this->getMockBuilder(Base::class)
			->setConstructorArgs([
				$this->userManager,
				$this->groupManager,
				$this->url,
			])
			->onlyMethods(['parse'])
			->getMock();
	}

	public static function dataSetSubjects(): array {
		return [
			['abc', []],
			['{actor} created {calendar}', ['actor' => ['name' => 'abc'], 'calendar' => ['name' => 'xyz']]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataSetSubjects')]
	public function testSetSubjects(string $subject, array $parameters): void {
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('setRichSubject')
			->with($subject, $parameters)
			->willReturnSelf();
		$event->expects($this->never())
			->method('setParsedSubject');

		$this->invokePrivate($this->provider, 'setSubjects', [$event, $subject, $parameters]);
	}

	public static function dataGenerateCalendarParameter(): array {
		return [
			[['id' => 23, 'uri' => 'foo', 'name' => 'bar'], 'bar'],
			[['id' => 42, 'uri' => 'foo', 'name' => 'Personal'], 'Personal'],
			[['id' => 42, 'uri' => 'personal', 'name' => 'bar'], 'bar'],
			[['id' => 42, 'uri' => 'personal', 'name' => 'Personal'], 't(Personal)'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGenerateCalendarParameter')]
	public function testGenerateCalendarParameter(array $data, string $name): void {
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return 't(' . vsprintf($string, $args) . ')';
			});

		$this->assertEquals([
			'type' => 'calendar',
			'id' => $data['id'],
			'name' => $name,
		], $this->invokePrivate($this->provider, 'generateCalendarParameter', [$data, $l]));
	}

	public static function dataGenerateLegacyCalendarParameter(): array {
		return [
			[23, 'c1'],
			[42, 'c2'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGenerateLegacyCalendarParameter')]
	public function testGenerateLegacyCalendarParameter(int $id, string $name): void {
		$this->assertEquals([
			'type' => 'calendar',
			'id' => $id,
			'name' => $name,
		], $this->invokePrivate($this->provider, 'generateLegacyCalendarParameter', [$id, $name]));
	}

	public static function dataGenerateGroupParameter(): array {
		return [
			['g1'],
			['g2'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGenerateGroupParameter')]
	public function testGenerateGroupParameter(string $gid): void {
		$this->assertEquals([
			'type' => 'user-group',
			'id' => $gid,
			'name' => $gid,
		], $this->invokePrivate($this->provider, 'generateGroupParameter', [$gid]));
	}
}
