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

use OC\Core\Command\Config\App\DeleteConfig;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DeleteConfigTest extends TestCase {
	/** @var IAppConfig|MockObject */
	protected $appConfig;
	/** @var InputInterface|MockObject */
	protected $consoleInput;
	/** @var OutputInterface|MockObject */
	protected $consoleOutput;
	protected Command $command;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new DeleteConfig($this->appConfig);
	}


	public static function dataDelete(): array {
		return [
			[
				'name',
				true,
				true,
				0,
				'info',
			],
			[
				'name',
				true,
				false,
				0,
				'info',
			],
			[
				'name',
				false,
				false,
				0,
				'info',
			],
			[
				'name',
				false,
				true,
				1,
				'error',
			],
		];
	}

	/**
	 * @dataProvider dataDelete
	 */
	public function testDelete(string $configName, bool $configExists, bool $checkIfExists, int $expectedReturn, string $expectedMessage): void {
		$this->appConfig->expects(($checkIfExists) ? $this->once() : $this->never())
			->method('getKeys')
			->with('app-name')
			->willReturn($configExists ? [$configName] : []);

		$this->appConfig->expects(($expectedReturn === 0) ? $this->once() : $this->never())
			->method('deleteKey')
			->with('app-name', $configName);

		$this->consoleInput->expects($this->exactly(2))
			->method('getArgument')
			->willReturnMap([
				['app', 'app-name'],
				['name', $configName],
			]);
		$this->consoleInput->method('hasParameterOption')
			->with('--error-if-not-exists')
			->willReturn($checkIfExists);

		$this->consoleOutput->method('writeln')
			->with($this->stringContains($expectedMessage));

		$this->assertSame($expectedReturn, self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]));
	}
}
