<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\App;

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DependencyAnalyzerTest extends TestCase {
	private Platform&MockObject $platformMock;

	private DependencyAnalyzer $analyser;

	protected function setUp(): void {
		$this->platformMock = $this->getMockBuilder(Platform::class)
			->disableOriginalConstructor()
			->getMock();
		$this->platformMock->expects($this->any())
			->method('getPhpVersion')
			->willReturn('5.4.3');
		$this->platformMock->expects($this->any())
			->method('getIntSize')
			->willReturn(4);
		$this->platformMock->expects($this->any())
			->method('getDatabase')
			->willReturn('mysql');
		$this->platformMock->expects($this->any())
			->method('getOS')
			->willReturn('Linux');
		$this->platformMock->expects($this->any())
			->method('isCommandKnown')
			->willReturnCallback(function ($command) {
				return ($command === 'grep');
			});
		$this->platformMock->expects($this->any())
			->method('getLibraryVersion')
			->willReturnCallback(function ($lib) {
				if ($lib === 'curl') {
					return '2.3.4';
				}
				return null;
			});
		$this->platformMock->expects($this->any())
			->method('getOcVersion')
			->willReturn('8.0.2');

		$this->analyser = new DependencyAnalyzer($this->platformMock);
	}

	/**
	 *
	 * @param string $expectedMissing
	 * @param string $minVersion
	 * @param string $maxVersion
	 * @param string $intSize
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesPhpVersion')]
	public function testPhpVersion($expectedMissing, $minVersion, $maxVersion, $intSize): void {
		$app = [
			'dependencies' => [
				'php' => []
			]
		];
		if (!is_null($minVersion)) {
			$app['dependencies']['php']['@attributes']['min-version'] = $minVersion;
		}
		if (!is_null($maxVersion)) {
			$app['dependencies']['php']['@attributes']['max-version'] = $maxVersion;
		}
		if (!is_null($intSize)) {
			$app['dependencies']['php']['@attributes']['min-int-size'] = $intSize;
		}
		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @param $expectedMissing
	 * @param $databases
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesDatabases')]
	public function testDatabases($expectedMissing, $databases): void {
		$app = [
			'dependencies' => [
			]
		];
		if (!is_null($databases)) {
			$app['dependencies']['database'] = $databases;
		}
		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 *
	 * @param string $expectedMissing
	 * @param string|null $commands
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesCommands')]
	public function testCommand($expectedMissing, $commands): void {
		$app = [
			'dependencies' => [
			]
		];
		if (!is_null($commands)) {
			$app['dependencies']['command'] = $commands;
		}
		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @param $expectedMissing
	 * @param $libs
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesLibs')]
	public function testLibs($expectedMissing, $libs): void {
		$app = [
			'dependencies' => [
			]
		];
		if (!is_null($libs)) {
			$app['dependencies']['lib'] = $libs;
		}

		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @param $expectedMissing
	 * @param $oss
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesOS')]
	public function testOS($expectedMissing, $oss): void {
		$app = [
			'dependencies' => []
		];
		if (!is_null($oss)) {
			$app['dependencies']['os'] = $oss;
		}

		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @param $expectedMissing
	 * @param $oc
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providesOC')]
	public function testOC($expectedMissing, $oc): void {
		$app = [
			'dependencies' => []
		];
		if (!is_null($oc)) {
			$app['dependencies'] = $oc;
		}

		$missing = $this->analyser->analyze($app);

		$this->assertTrue(is_array($missing));
		$this->assertEquals($expectedMissing, $missing);
	}

	/**
	 * @return array
	 */
	public static function providesOC(): array {
		return [
			// no version -> no missing dependency
			[
				[],
				null,
			],
			[
				[],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '8',
							'max-version' => '8',
						],
					],
				],
			],
			[
				[],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '8.0',
							'max-version' => '8.0',
						],
					],
				],
			],
			[
				[],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '8.0.2',
							'max-version' => '8.0.2'
						],
					],
				],
			],
			[
				[
					'Server version 8.0.3 or higher is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '8.0.3'
						],
					],
				],
			],
			[
				[
					'Server version 9 or higher is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '9'
						],
					],
				],
			],
			[
				[
					'Server version 10 or higher is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '10'
						],
					],
					'owncloud' => [
						'@attributes' => [
							'min-version' => '9'
						],
					],
				],
			],
			[
				[
					'Server version 9.2 or higher is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '9.2',
						],
					],
				],
			],
			[
				[
					'Server version 11 or higher is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'min-version' => '11',
						],
					],
				],
			],
			[
				[
					'Server version 8.0.1 or lower is required.',
				],
				[
					'nextcloud' => [
						'@attributes' => [
							'max-version' => '8.0.1',
						],
					],
				],
			],
			[
				[],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '8',
							'max-version' => '8',
						],
					],
				],
			],
			[
				[],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '8.0',
							'max-version' => '8.0',
						],
					],
				],
			],
			[
				[],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '8.0.2',
							'max-version' => '8.0.2'
						],
					],
				],
			],
			[
				[
					'Server version 8.0.3 or higher is required.',
				],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '8.0.3'
						],
					],
				],
			],
			[
				[
					'Server version 9 or higher is required.',
				],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '9'
						],
					],
				],
			],
			[
				[
					'Server version 9.2 or higher is required.',
				],
				[
					'owncloud' => [
						'@attributes' => [
							'min-version' => '9.2',
						],
					],
				],
			],
			[
				[
					'Server version 8.0.1 or lower is required.',
				],
				[
					'owncloud' => [
						'@attributes' => [
							'max-version' => '8.0.1',
						],
					],
				],
			],
		];
	}

	/**
	 * @return array
	 */
	public static function providesOS(): array {
		return [
			[[], null],
			[[], []],
			[['The following platforms are supported: ANDROID'], 'ANDROID'],
			[['The following platforms are supported: WINNT'], ['WINNT']]
		];
	}

	/**
	 * @return array
	 */
	public static function providesLibs(): array {
		return [
			// we expect curl to exist
			[[], 'curl'],
			// we expect abcde to exist
			[['The library abcde is not available.'], ['abcde']],
			// curl in version 100.0 does not exist
			[['Library curl with a version higher than 100.0 is required - available version 2.3.4.'],
				[['@attributes' => ['min-version' => '100.0'], '@value' => 'curl']]],
			// curl in version 100.0 does not exist
			[['Library curl with a version lower than 1.0.0 is required - available version 2.3.4.'],
				[['@attributes' => ['max-version' => '1.0.0'], '@value' => 'curl']]],
			[['Library curl with a version lower than 2.3.3 is required - available version 2.3.4.'],
				[['@attributes' => ['max-version' => '2.3.3'], '@value' => 'curl']]],
			[['Library curl with a version higher than 2.3.5 is required - available version 2.3.4.'],
				[['@attributes' => ['min-version' => '2.3.5'], '@value' => 'curl']]],
			[[],
				[['@attributes' => ['min-version' => '2.3.4', 'max-version' => '2.3.4'], '@value' => 'curl']]],
			[[],
				[['@attributes' => ['min-version' => '2.3', 'max-version' => '2.3'], '@value' => 'curl']]],
			[[],
				[['@attributes' => ['min-version' => '2', 'max-version' => '2'], '@value' => 'curl']]],
			[[],
				['@attributes' => ['min-version' => '2', 'max-version' => '2'], '@value' => 'curl']],
		];
	}

	/**
	 * @return array
	 */
	public static function providesCommands(): array {
		return [
			[[], null],
			// grep is known on linux
			[[], [['@attributes' => ['os' => 'Linux'], '@value' => 'grep']]],
			// grepp is not known on linux
			[['The command line tool grepp could not be found'], [['@attributes' => ['os' => 'Linux'], '@value' => 'grepp']]],
			// we don't care about tools on Windows - we are on Linux
			[[], [['@attributes' => ['os' => 'Windows'], '@value' => 'grepp']]],
			// grep is known on all systems
			[[], 'grep'],
			[[], ['@attributes' => ['os' => 'Linux'], '@value' => 'grep']],
		];
	}

	/**
	 * @return array
	 */
	public static function providesDatabases(): array {
		return [
			// non BC - in case on databases are defined -> all are supported
			[[], null],
			[[], []],
			[['The following databases are supported: mongodb'], 'mongodb'],
			[['The following databases are supported: sqlite, postgres'], ['sqlite', ['@value' => 'postgres']]],
		];
	}

	/**
	 * @return array
	 */
	public static function providesPhpVersion(): array {
		return [
			[[], null, null, null],
			[[], '5.4', null, null],
			[[], null, '5.5', null],
			[[], '5.4', '5.5', null],
			[['PHP 5.4.4 or higher is required.'], '5.4.4', null, null],
			[['PHP with a version lower than 5.4.2 is required.'], null, '5.4.2', null],
			[['64bit or higher PHP required.'], null, null, 64],
			[[], '5.4', '5.4', null],
		];
	}

	public static function appVersionsProvider(): array {
		return [
			// exact match
			[
				'6.0.0.0',
				[
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				],
				true
			],
			// in-between match
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
				],
				true
			],
			// app too old
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '5.0',
				],
				false
			],
			// app too new
			[
				'5.0.0.0',
				[
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				],
				false
			],
			// only min specified
			[
				'6.0.0.0',
				[
					'requiremin' => '6.0',
				],
				true
			],
			// only min specified fail
			[
				'5.0.0.0',
				[
					'requiremin' => '6.0',
				],
				false
			],
			// only min specified legacy
			[
				'6.0.0.0',
				[
					'require' => '6.0',
				],
				true
			],
			// only min specified legacy fail
			[
				'4.0.0.0',
				[
					'require' => '6.0',
				],
				false
			],
			// only max specified
			[
				'5.0.0.0',
				[
					'requiremax' => '6.0',
				],
				true
			],
			// only max specified fail
			[
				'7.0.0.0',
				[
					'requiremax' => '6.0',
				],
				false
			],
			// variations of versions
			// single OC number
			[
				'4',
				[
					'require' => '4.0',
				],
				true
			],
			// multiple OC number
			[
				'4.3.1',
				[
					'require' => '4.3',
				],
				true
			],
			// single app number
			[
				'4',
				[
					'require' => '4',
				],
				true
			],
			// single app number fail
			[
				'4.3',
				[
					'require' => '5',
				],
				false
			],
			// complex
			[
				'5.0.0',
				[
					'require' => '4.5.1',
				],
				true
			],
			// complex fail
			[
				'4.3.1',
				[
					'require' => '4.3.2',
				],
				false
			],
			// two numbers
			[
				'4.3.1',
				[
					'require' => '4.4',
				],
				false
			],
			// one number fail
			[
				'4.3.1',
				[
					'require' => '5',
				],
				false
			],
			// pre-alpha app
			[
				'5.0.3',
				[
					'require' => '4.93',
				],
				true
			],
			// pre-alpha OC
			[
				'6.90.0.2',
				[
					'require' => '6.90',
				],
				true
			],
			// pre-alpha OC max
			[
				'6.90.0.2',
				[
					'requiremax' => '7',
				],
				true
			],
			// expect same major number match
			[
				'5.0.3',
				[
					'require' => '5',
				],
				true
			],
			// expect same major number match
			[
				'5.0.3',
				[
					'requiremax' => '5',
				],
				true
			],
			// dependencies versions before require*
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '7.0',
								'max-version' => '7.0',
							],
						],
					],
				],
				false
			],
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '5.0',
								'max-version' => '5.0',
							],
						],
					],
				],
				false
			],
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '5.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '5.0',
								'max-version' => '7.0',
							],
						],
					],
				],
				true
			],
			[
				'9.2.0.0',
				[
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '9.0',
								'max-version' => '9.1',
							],
						],
						'nextcloud' => [
							'@attributes' => [
								'min-version' => '9.1',
								'max-version' => '9.2',
							],
						],
					],
				],
				true
			],
			[
				'9.2.0.0',
				[
					'dependencies' => [
						'nextcloud' => [
							'@attributes' => [
								'min-version' => '9.1',
								'max-version' => '9.2',
							],
						],
					],
				],
				true
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('appVersionsProvider')]
	public function testServerVersion($ncVersion, $appInfo, $expectedResult): void {
		$this->assertEquals($expectedResult, count($this->analyser->analyzeServerVersion($ncVersion, $appInfo, false)) === 0);
	}
}
