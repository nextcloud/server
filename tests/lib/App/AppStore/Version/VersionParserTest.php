<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Version;

use OC\App\AppStore\Version\Version;
use OC\App\AppStore\Version\VersionParser;
use Test\TestCase;

class VersionParserTest extends TestCase {
	/** @var VersionParser */
	private $versionParser;

	protected function setUp(): void {
		parent::setUp();
		$this->versionParser = new VersionParser();
	}

	/**
	 * @return array
	 */
	public function versionProvider() {
		return [
			[
				'*',
				new Version('', ''),
			],
			[
				'<=8.1.2',
				new Version('', '8.1.2'),
			],
			[
				'<=9',
				new Version('', '9'),
			],
			[
				'>=9.3.2',
				new Version('9.3.2', ''),
			],
			[
				'>=8.1.2 <=9.3.2',
				new Version('8.1.2', '9.3.2'),
			],
			[
				'>=8.2 <=9.1',
				new Version('8.2', '9.1'),
			],
			[
				'>=9 <=11',
				new Version('9', '11'),
			],
		];
	}

	/**
	 * @dataProvider versionProvider
	 *
	 * @param string $input
	 * @param Version $expected
	 */
	public function testGetVersion($input,
		Version $expected): void {
		$this->assertEquals($expected, $this->versionParser->getVersion($input));
	}

	
	public function testGetVersionException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Version cannot be parsed: BogusVersion');

		$this->versionParser->getVersion('BogusVersion');
	}

	
	public function testGetVersionExceptionWithMultiple(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Version cannot be parsed: >=8.2 <=9.1a');

		$this->versionParser->getVersion('>=8.2 <=9.1a');
	}
}
