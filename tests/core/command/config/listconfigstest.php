<?php
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

namespace Tests\Core\Command\Config;


use OC\Core\Command\Config\ListConfigs;
use OC\Core\Command\Config\NoValue;
use Test\TestCase;

class ListConfigsTest extends TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $appConfig;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $systemConfig;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$systemConfig = $this->systemConfig = $this->getMockBuilder('OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();
		$appConfig = $this->appConfig = $this->getMockBuilder('OCP\IAppConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->consoleOutput = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

		/** @var \OC\SystemConfig $systemConfig */
		/** @var \OCP\IAppConfig $appConfig */
		$this->command = new ListConfigs($systemConfig, $appConfig);
	}


	public function dataDisable() {
		return [
			[
				'all',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				true,
				json_encode([
					'core' => [
						'global_cache_gc_lastrun' => '1430388388',
					],
					'files' => [
						'enabled' => 'yes',
					],
					'system' => [
						'overwrite.cli.url' => 'http://localhost',
					],
				]),
			],
			[
				'all',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				false,
				json_encode([
					'core' => [
						'global_cache_gc_lastrun' => '1430388388',
					],
					'files' => [
						'enabled' => 'yes',
					],
					'system' => [
						'secret' => 'my secret',
						'overwrite.cli.url' => 'http://localhost',
					],
				]),
			],
			[
				'system',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				true,
				json_encode([
					'overwrite.cli.url' => 'http://localhost',
				]),
			],
			[
				'system',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				false,
				json_encode([
					'secret' => 'my secret',
					'overwrite.cli.url' => 'http://localhost',
				]),
			],
			[
				'files',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				true,
				json_encode([
					'enabled' => 'yes',
				]),
			],
			[
				'files',
				// config.php
				[
					'secret',
					'overwrite.cli.url',
				],
				[
					['secret', 'N;', 'my secret'],
					['overwrite.cli.url', 'N;', 'http://localhost'],
				],
				// app config
				[
					['files', false, [
						'enabled' => 'yes',
					]],
					['core', false, [
						'global_cache_gc_lastrun' => '1430388388',
					]],
				],
				false,
				json_encode([
					'enabled' => 'yes',
				]),
			],
		];
	}

	/**
	 * @dataProvider dataDisable
	 *
	 * @param string $app
	 * @param array $systemConfigs
	 * @param array $systemConfigMap
	 * @param array $appConfig
	 * @param bool $public
	 * @param string $expected
	 */
	public function testList($app, $systemConfigs, $systemConfigMap, $appConfig, $public, $expected) {
		$this->systemConfig->expects($this->any())
			->method('getKeys')
			->willReturn($systemConfigs);
		$this->systemConfig->expects($this->any())
			->method('getValue')
			->willReturnMap($systemConfigMap);

		$this->appConfig->expects($this->any())
			->method('getApps')
			->willReturn(['core', 'files']);
		$this->appConfig->expects($this->any())
			->method('getValues')
			->willReturnMap($appConfig);

		$this->consoleInput->expects($this->once())
			->method('getArgument')
			->with('app')
			->willReturn($app);

		$this->consoleInput->expects($this->any())
			->method('getOption')
			->willReturnMap([
				['output', 'json'],
				['public', $public],
			]);

		global $output;

		$output = '';
		$this->consoleOutput->expects($this->any())
			->method('writeln')
			->willReturnCallback(function($value) {
				global $output;
				$output .= $value . "\n";
				return $output;
			});

		\Test_Helper::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);

		$this->assertEquals($expected, trim($output, "\n"));
	}
}
