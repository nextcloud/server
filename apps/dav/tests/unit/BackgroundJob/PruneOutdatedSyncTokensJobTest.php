<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\unit\BackgroundJob;

use InvalidArgumentException;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\BackgroundJob\PruneOutdatedSyncTokensJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PruneOutdatedSyncTokensJobTest extends TestCase {
	/** @var ITimeFactory | MockObject */
	private $timeFactory;

	/** @var CalDavBackend | MockObject */
	private $calDavBackend;

	/** @var CardDavBackend | MockObject */
	private $cardDavBackend;

	/** @var IConfig|MockObject */
	private $config;

	/** @var LoggerInterface|MockObject*/
	private $logger;

	private PruneOutdatedSyncTokensJob $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->backgroundJob = new PruneOutdatedSyncTokensJob($this->timeFactory, $this->calDavBackend, $this->cardDavBackend, $this->config, $this->logger);
	}

	/**
	 * @dataProvider dataForTestRun
	 */
	public function testRun(string $configToKeep, string $configRetentionDays, int $actualLimit, int $retentionDays, int $deletedCalendarSyncTokens, int $deletedAddressBookSyncTokens): void {
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->with(Application::APP_ID, self::anything(), self::anything())
			->willReturnCallback(function ($app, $key) use ($configToKeep, $configRetentionDays) {
				switch ($key) {
					case 'totalNumberOfSyncTokensToKeep':
						return $configToKeep;
					case 'syncTokensRetentionDays':
						return $configRetentionDays;
					default:
						throw new InvalidArgumentException();
				}
			});
		$this->calDavBackend->expects($this->once())
			->method('pruneOutdatedSyncTokens')
			->with($actualLimit)
			->willReturn($deletedCalendarSyncTokens);
		$this->cardDavBackend->expects($this->once())
			->method('pruneOutdatedSyncTokens')
			->with($actualLimit, $retentionDays)
			->willReturn($deletedAddressBookSyncTokens);
		$this->logger->expects($this->once())
			->method('info')
			->with('Pruned {calendarSyncTokensNumber} calendar sync tokens and {addressBooksSyncTokensNumber} address book sync tokens', [
				'calendarSyncTokensNumber' => $deletedCalendarSyncTokens,
				'addressBooksSyncTokensNumber' => $deletedAddressBookSyncTokens
			]);

		$this->backgroundJob->run(null);
	}

	public function dataForTestRun(): array {
		return [
			['100', '2', 100, 7 * 24 * 3600, 2, 3],
			['100', '14', 100, 14 * 24 * 3600, 2, 3],
			['0', '60', 1, 60 * 24 * 3600, 0, 0]
		];
	}
}
