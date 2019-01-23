<?php
/**
 * @copyright 2019, Thomas Citharel <tcit@tcit.fr>
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
namespace OCA\DAV\Tests\Command;

use OCA\DAV\Command\SyncSystemAddressBook;
use OCA\DAV\CardDAV\SyncService;
use OCP\IConfig;
use InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;
/**
 * Class SyncSystemAddressBookTest
 *
 * @package OCA\DAV\Tests\Command
 */
class SyncSystemAddressBookTest extends TestCase {

    /** @var SyncService */
    private $syncService;

    /** @var IConfig */
    private $config;

    /** @var SyncSystemAddressBook */
    private $command;

    protected function setUp() {
		parent::setUp();
		$this->syncService = $this->createMock(SyncService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->command = new SyncSystemAddressBook(
			$this->syncService,
			$this->config
		);
    }

    public function testSyncEnabled()
    {
        $this->config->method('getAppValue')->with('dav', 'syncSystemAddressbook', 'yes')->willReturn('yes');
        $this->syncService->expects($this->once())->method('syncInstance');
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);
    }

    public function testSyncDisabled()
    {
        $this->config->method('getAppValue')->with('dav', 'syncSystemAddressbook', 'yes')->willReturn('no');
        $this->syncService->expects($this->once())->method('purgeSystemAddressBook');
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);
    }
}