<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Settings\Admin;

use OC\Settings\Admin\Logging;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use Test\TestCase;
use OC\Log\Owncloud as LogFile;

class LoggingTest extends TestCase {
	/** @var Logging */
	private $admin;
	/** @var IConfig */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();

		$this->admin = new Logging(
			$this->config
		);
	}

	public function testGetForm() {
		$this->config
			->expects($this->at(0))
			->method('getSystemValue')
			->with('log_type', 'file')
			->willReturn('owncloud');
		$this->config
			->expects($this->at(1))
			->method('getSystemValue')
			->with('loglevel', 2)
			->willReturn(3);

		$numEntriesToLoad = 5;
		$entries = LogFile::getEntries($numEntriesToLoad + 1);
		$entriesRemaining = count($entries) > $numEntriesToLoad;
		$entries = array_slice($entries, 0, $numEntriesToLoad);

		$logFileExists = file_exists(LogFile::getLogFilePath()) ;
		$logFileSize = $logFileExists ? filesize(LogFile::getLogFilePath()) : 0;

		$expected = new TemplateResponse(
			'settings',
			'admin/logging',
			[
				'loglevel'         => 3,
				'entries'          => $entries,
				'entriesremain'    => $entriesRemaining,
				'doesLogFileExist' => $logFileExists,
				'logFileSize'      => $logFileSize,
				'showLog'          => true,
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('logging', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
