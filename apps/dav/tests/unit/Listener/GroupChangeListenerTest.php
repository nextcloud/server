<?php

declare(strict_types=1);

/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
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
namespace OCA\DAV\Tests\Unit\Listener;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\BackgroundJob\SyncSystemAddressBookAfterUsersChange;
use OCA\DAV\Listener\GroupChangeListener;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\BeforeGroupChangedEvent;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroupChangeListenerTest extends TestCase {
	private IJobList|MockObject $jobList;
	private IConfig|MockObject $config;
	private GroupChangeListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->config = $this->createMock(IConfig::class);

		$this->listener = new GroupChangeListener(
			$this->jobList,
			$this->config
		);
	}

	/**
	 * @dataProvider dataForTestHandleGroupChangeEvent
	 */
	public function testHandleGroupChangeEvent(Event $event, array $users, string $groupsExposed, bool $willSync): void {
		$this->jobList->expects($willSync ? $this->once() : $this->never())->method('add')->with(SyncSystemAddressBookAfterUsersChange::class, $users);
		$this->config->expects($event instanceof BeforeGroupCreatedEvent ? $this->never() : $this->once())->method('getAppValue')->with(Application::APP_ID, 'system_addressbook_expose_groups', 'no')->willReturn($groupsExposed);
		$this->listener->handle($event);
	}

	public function dataForTestHandleGroupChangeEvent(): array {
		$group = $this->createMock(IGroup::class);
		$users = [$this->createMock(IUser::class), $this->createMock(IUser::class)];
		$group->expects($this->any())->method('getUsers')->willReturn($users);
		return [
			[new BeforeGroupChangedEvent($group, 'displayName', 'NewDisplayName', 'OldDisplayName'), $users, 'no', false],
			[new BeforeGroupChangedEvent($group, 'displayName', 'NewDisplayName', 'OldDisplayName'), $users, 'yes', true],
			[new BeforeGroupDeletedEvent($group), $users, 'no', false],
			[new BeforeGroupDeletedEvent($group), $users, 'yes', true],
			[new BeforeGroupCreatedEvent('mygroup'), $users, 'no', false],
			[new BeforeGroupCreatedEvent('mygroup'), $users, 'yes', false],
		];
	}
}
