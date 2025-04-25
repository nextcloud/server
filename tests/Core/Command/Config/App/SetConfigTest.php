<?php

declare(strict_types=1);
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Tests\Core\Command\Config\App;

use OC\AppConfig;
use OC\Core\Command\Config\App\SetConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SetConfigTest extends TestCase {
	/** @var IAppConfig|MockObject */
	protected $appConfig;
	/** @var InputInterface|MockObject */
	protected $consoleInput;
	/** @var OutputInterface|MockObject */
	protected $consoleOutput;
	protected Command $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(AppConfig::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new SetConfig($this->appConfig);
	}


	public static function dataSet(): array {
		return [
			[
				'name',
				'newvalue',
				true,
				true,
				true,
				'info',
			],
			[
				'name',
				'newvalue',
				false,
				true,
				false,
				'comment',
			],
		];
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSet(string $configName, mixed $newValue, bool $configExists, bool $updateOnly, bool $updated, string $expectedMessage): void {
		$this->appConfig->method('hasKey')
			->with('app-name', $configName)
			->willReturn($configExists);

		if (!$configExists) {
			$this->appConfig->method('getValueType')
				->willThrowException(new AppConfigUnknownKeyException());
		} else {
			$this->appConfig->method('getValueType')
				->willReturn(IAppConfig::VALUE_MIXED);
		}

		if ($updated) {
			$this->appConfig->expects($this->once())
				->method('setValueMixed')
				->with('app-name', $configName, $newValue);
		}

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['value', $newValue],
				['lazy', null],
				['sensitive', null],
				['no-interaction', true],
			]);
		$this->consoleInput->method('hasParameterOption')
			->willReturnMap([
				['--type', false, false],
				['--value', false, true],
				['--update-only', false, $updateOnly]
			]);
		$this->consoleOutput->method('writeln')
			->with($this->stringContains($expectedMessage));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
