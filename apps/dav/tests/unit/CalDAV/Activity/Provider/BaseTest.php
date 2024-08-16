<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Provider;

use OCA\DAV\CalDAV\Activity\Provider\Base;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BaseTest extends TestCase {
	/** @var IUserManager|MockObject */
	protected $userManager;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var IURLGenerator|MockObject */
	protected $url;

	/** @var IProvider|Base|MockObject */
	protected $provider;

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
			->setMethods(['parse'])
			->getMock();
	}

	public function dataSetSubjects() {
		return [
			['abc', [], 'abc'],
			['{actor} created {calendar}', ['actor' => ['name' => 'abc'], 'calendar' => ['name' => 'xyz']], 'abc created xyz'],
		];
	}

	/**
	 * @dataProvider dataSetSubjects
	 * @param string $subject
	 * @param array $parameters
	 * @param string $parsedSubject
	 */
	public function testSetSubjects(string $subject, array $parameters, string $parsedSubject): void {
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('setRichSubject')
			->with($subject, $parameters)
			->willReturnSelf();
		$event->expects($this->never())
			->method('setParsedSubject');

		$this->invokePrivate($this->provider, 'setSubjects', [$event, $subject, $parameters]);
	}

	public function dataGenerateCalendarParameter() {
		return [
			[['id' => 23, 'uri' => 'foo', 'name' => 'bar'], 'bar'],
			[['id' => 42, 'uri' => 'foo', 'name' => 'Personal'], 'Personal'],
			[['id' => 42, 'uri' => 'personal', 'name' => 'bar'], 'bar'],
			[['id' => 42, 'uri' => 'personal', 'name' => 'Personal'], 't(Personal)'],
		];
	}

	/**
	 * @dataProvider dataGenerateCalendarParameter
	 * @param array $data
	 * @param string $name
	 */
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

	public function dataGenerateLegacyCalendarParameter() {
		return [
			[23, 'c1'],
			[42, 'c2'],
		];
	}

	/**
	 * @dataProvider dataGenerateLegacyCalendarParameter
	 * @param int $id
	 * @param string $name
	 */
	public function testGenerateLegacyCalendarParameter(int $id, string $name): void {
		$this->assertEquals([
			'type' => 'calendar',
			'id' => $id,
			'name' => $name,
		], $this->invokePrivate($this->provider, 'generateLegacyCalendarParameter', [$id, $name]));
	}

	public function dataGenerateGroupParameter() {
		return [
			['g1'],
			['g2'],
		];
	}

	/**
	 * @dataProvider dataGenerateGroupParameter
	 * @param string $gid
	 */
	public function testGenerateGroupParameter(string $gid): void {
		$this->assertEquals([
			'type' => 'user-group',
			'id' => $gid,
			'name' => $gid,
		], $this->invokePrivate($this->provider, 'generateGroupParameter', [$gid]));
	}
}
