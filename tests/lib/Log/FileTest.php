<?php
/**
 *
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\File;
use OC\SystemConfig;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Server;
use Test\TestCase;

/**
 * Class FileTest
 */
class FileTest extends TestCase {
	private $restore_logfile;
	private $restore_logdateformat;

	/** @var File */
	protected $logFile;

	protected function setUp(): void {
		parent::setUp();
		$config = Server::get(SystemConfig::class);
		$this->restore_logfile = $config->getValue('logfile');
		$this->restore_logdateformat = $config->getValue('logdateformat');

		$config->setValue('logfile', $config->getValue('datadirectory') . '/logtest.log');
		$this->logFile = new File($config->getValue('datadirectory') . '/logtest.log', '', $config);
	}
	protected function tearDown(): void {
		$config = Server::get(SystemConfig::class);
		if (isset($this->restore_logfile)) {
			$config->getValue('logfile', $this->restore_logfile);
		} else {
			$config->deleteValue('logfile');
		}
		if (isset($this->restore_logdateformat)) {
			$config->getValue('logdateformat', $this->restore_logdateformat);
		} else {
			$config->deleteValue('logdateformat');
		}
		$this->logFile = new File($this->restore_logfile, '', $config);
		parent::tearDown();
	}

	public function testLogging(): void {
		$config = Server::get(IConfig::class);
		# delete old logfile
		unlink($config->getSystemValue('logfile'));

		# set format & write log line
		$config->setSystemValue('logdateformat', 'u');
		$this->logFile->write('code', ['something' => 'extra', 'message' => 'Testing logging'], ILogger::ERROR);

		# read log line
		$handle = @fopen($config->getSystemValue('logfile'), 'r');
		$line = fread($handle, 1000);
		fclose($handle);

		# check log has data content
		$values = (array)json_decode($line, true);
		$this->assertArrayNotHasKey('message', $values['data']);
		$this->assertEquals('extra', $values['data']['something']);
		$this->assertEquals('Testing logging', $values['message']);
	}

	public function testMicrosecondsLogTimestamp(): void {
		$config = Server::get(IConfig::class);
		# delete old logfile
		unlink($config->getSystemValue('logfile'));

		# set format & write log line
		$config->setSystemValue('logdateformat', 'u');
		$this->logFile->write('test', 'message', ILogger::ERROR);

		# read log line
		$handle = @fopen($config->getSystemValue('logfile'), 'r');
		$line = fread($handle, 1000);
		fclose($handle);

		# check timestamp has microseconds part
		$values = (array)json_decode($line);
		$microseconds = $values['time'];
		$this->assertNotEquals(0, $microseconds);
	}
}
