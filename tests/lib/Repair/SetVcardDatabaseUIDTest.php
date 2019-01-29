<?php
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

namespace Test\Repair;

use OCP\IConfig;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OC\Repair\NC15\SetVcardDatabaseUID;
use Test\TestCase;

/**
 * @group DB
 */
class SetVcardDatabaseUIDTest extends TestCase {

	/** @var SetVcardDatabaseUID */
	private $repair;

	/** @var IConfig */
	private $config;

	/** @var Ilogger */
	private $logger;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(Ilogger::class);
		$this->repair = new SetVcardDatabaseUID(\OC::$server->getDatabaseConnection(), $this->config, $this->logger);
	}

	protected function tearDown() {
		return parent::tearDown();
	}

	public function dataTestVcards() {
		return [
			// classic vcard
			['BEGIN:VCARD'.PHP_EOL.
			'VERSION:3.0'.PHP_EOL.
			'PRODID:-//Sabre//Sabre VObject 4.1.2//EN'.PHP_EOL.
			'UID:Test'.PHP_EOL.
			'FN:Test'.PHP_EOL.
			'N:Test;;;;'.PHP_EOL.
			'END:VCARD', 'Test'],

			// UID as url
			['BEGIN:VCARD'.PHP_EOL.
			'VERSION:3.0'.PHP_EOL.
			'PRODID:-//Sabre//Sabre VObject 4.1.2//EN'.PHP_EOL.
			'UID:https://User@old.domain.com/remote.php/carddav/addressbooks/User/contacts/2EAF6525-17ADC861-38D6BB1D.vcf'.PHP_EOL.
			'FN:Test'.PHP_EOL.
			'N:Test;;;;'.PHP_EOL.
			'END:VCARD', 'https://User@old.domain.com/remote.php/carddav/addressbooks/User/contacts/2EAF6525-17ADC861-38D6BB1D.vcf'],

			// No uid
			['BEGIN:VCARD'.PHP_EOL.
			'VERSION:3.0'.PHP_EOL.
			'PRODID:-//Sabre//Sabre VObject 4.1.2//EN'.PHP_EOL.
			'FN:Test'.PHP_EOL.
			'N:Test;;;;'.PHP_EOL.
			'END:VCARD', false]
		];
	}

	/**
	 * @dataProvider dataTestVcards
	 *
	 * @param string $from
	 * @param string|boolean $expected
	 */
	public function testExtractUIDFromVcard($from, $expected) {
		$output = $this->createMock(IOutput::class);
		$uid = $this->invokePrivate($this->repair, 'getUid', ['carddata' => $from, 'output' => $output]);
		$this->assertEquals($expected, $uid);
	}

	public function shouldRunDataProvider() {
		return [
			['11.0.0.0', true],
			['15.0.0.3', false],
			['13.0.5.2', true],
			['12.0.0.0', true],
			['16.0.0.1', false],
			['15.0.0.2', true],
			['13.0.0.0', true],
			['13.0.0.1', true]
		];
	}

	/**
	 * @dataProvider shouldRunDataProvider
	 *
	 * @param string $from
	 * @param boolean $expected
	 */
	public function testShouldRun($from, $expected) {
		$this->config->expects($this->any())
		       ->method('getSystemValue')
		       ->with('version', '0.0.0.0')
		       ->willReturn($from);

		$this->assertEquals($expected, $this->invokePrivate($this->repair, 'shouldRun'));
	}

}
