<?php
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
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

use OCA\DAV\CalDAV\Reminder\NotificationProvider\EmailProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;
use OCA\DAV\CalDAV\Reminder\NotificationProvider\PushProvider;
use OCA\DAV\CalDAV\Reminder\NotificationProviderManager;
use OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException;
use OCA\DAV\Capabilities;
use Test\TestCase;

class NotificationProviderManagerTest extends TestCase {

    /** @var NotificationProviderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $providerManager;

	/**
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function setUp() {
		parent::setUp();

        $this->providerManager = new NotificationProviderManager();
        $this->providerManager->registerProvider(EmailProvider::class);
    }

	/**
	 * @expectedException OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException
	 * @expectedExceptionMessage Type NOT EXISTENT is not an accepted type of notification
	 * @throws ProviderNotAvailableException
	 * @throws NotificationTypeDoesNotExistException
	 */
    public function testGetProviderForUnknownType(): void
    {
        $this->providerManager->getProvider('NOT EXISTENT');
    }

	/**
	 * @expectedException OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException
	 * @expectedExceptionMessage No notification provider for type AUDIO available
	 * @throws NotificationTypeDoesNotExistException
	 * @throws ProviderNotAvailableException
	 */
    public function testGetProviderForUnRegisteredType(): void
    {
        $this->providerManager->getProvider('AUDIO');
    }

	/**
	 * @throws NotificationTypeDoesNotExistException
	 * @throws ProviderNotAvailableException
	 */
	public function testGetProvider(): void
    {
        $provider = $this->providerManager->getProvider('EMAIL');
        $this->assertInstanceOf(EmailProvider::class, $provider);
    }

	/**
	 * @throws NotificationTypeDoesNotExistException
	 * @throws ProviderNotAvailableException
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function testRegisterProvider(): void
	{
		$this->providerManager->registerProvider(PushProvider::class);
		$provider = $this->providerManager->getProvider('DISPLAY');
		$this->assertInstanceOf(PushProvider::class, $provider);
	}

	/**
	 * @expectedExceptionMessage Invalid notification provider registered
	 * @expectedException \InvalidArgumentException
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function testRegisterBadProvider(): void
	{
		$this->providerManager->registerProvider(Capabilities::class);
	}
}
