<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Provider;

use OCA\DAV\CalDAV\Activity\Provider\Base;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
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
			->onlyMethods(['parse'])
			->getMock();
	}

	public function dataSetSubjects(): array {
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
	public function testSetSubjects(string $subject, array $parameters, string $parsedSubject) {
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('setRichSubject')
			->with($subject, $parameters)
			->willReturnSelf();
		$event->expects($this->once())
			->method('setParsedSubject')
			->with($parsedSubject)
			->willReturnSelf();

		$this->invokePrivate($this->provider, 'setSubjects', [$event, $subject, $parameters]);
	}

	public function dataGenerateCalendarParameter(): array {
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
	public function testGenerateCalendarParameter(array $data, string $name) {
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

	public function dataGenerateLegacyCalendarParameter(): array {
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
	public function testGenerateLegacyCalendarParameter(int $id, string $name) {
		$this->assertEquals([
			'type' => 'calendar',
			'id' => $id,
			'name' => $name,
		], $this->invokePrivate($this->provider, 'generateLegacyCalendarParameter', [$id, $name]));
	}

	public function dataGenerateGroupParameter(): array {
		return [
			['g1'],
			['g2'],
		];
	}

	/**
	 * @dataProvider dataGenerateGroupParameter
	 * @param string $gid
	 */
	public function testGenerateGroupParameter(string $gid) {
		$this->assertEquals([
			'type' => 'user-group',
			'id' => $gid,
			'name' => $gid,
		], $this->invokePrivate($this->provider, 'generateGroupParameter', [$gid]));
	}

	public function dataGenerateUserParameter(): array {
		$u1 = $this->createMock(IUser::class);
		$u1->expects($this->any())
			->method('getDisplayName')
			->willReturn('User 1');
		return [
			['u1', 'User 1', $u1],
			['u2', 'u2', null],
		];
	}

	/**
	 * @dataProvider dataGenerateUserParameter
	 * @param string $uid
	 * @param string $displayName
	 * @param IUser|null $user
	 */
	public function testGenerateUserParameter(string $uid, string $displayName, ?IUser $user) {
		$this->userManager->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user);

		$this->assertEquals([
			'type' => 'user',
			'id' => $uid,
			'name' => $displayName,
		], $this->invokePrivate($this->provider, 'generateUserParameter', [$uid]));

		// Test caching (only 1 user manager invocation allowed)
		$this->assertEquals([
			'type' => 'user',
			'id' => $uid,
			'name' => $displayName,
		], $this->invokePrivate($this->provider, 'generateUserParameter', [$uid]));
	}
}
