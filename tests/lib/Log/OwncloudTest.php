<?php
/**
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\Log;

use OC\Log\Owncloud;
use Test\TestCase;

/**
 * Class OwncloudTest
 *
 * @group DB
 */
class OwncloudTest extends TestCase
{
	private $restore_logfile;
	private $restore_logdateformat;

	protected function setUp() {
		parent::setUp();
		$config = \OC::$server->getConfig();
		$this->restore_logfile = $config->getSystemValue("logfile");
		$this->restore_logdateformat = $config->getSystemValue('logdateformat');
		
		$config->setSystemValue("logfile", $config->getSystemValue('datadirectory') . "/logtest");
		Owncloud::init();
	}
	protected function tearDown() {
		$config = \OC::$server->getConfig();
		if (isset($this->restore_logfile)) {
			$config->getSystemValue("logfile", $this->restore_logfile);
		} else {
			$config->deleteSystemValue("logfile");
		}		
		if (isset($this->restore_logdateformat)) {
			$config->getSystemValue("logdateformat", $this->restore_logdateformat);
		} else {
			$config->deleteSystemValue("restore_logdateformat");
		}		
		Owncloud::init();
		parent::tearDown();
	}
	
	public function testMicrosecondsLogTimestamp() {
		$config = \OC::$server->getConfig();
		# delete old logfile
		unlink($config->getSystemValue('logfile'));

		# set format & write log line
		$config->setSystemValue('logdateformat', 'u');
		Owncloud::write('test', 'message', \OCP\Util::ERROR);
		
		# read log line
		$handle = @fopen($config->getSystemValue('logfile'), 'r');
		$line = fread($handle, 1000);
		fclose($handle);
		
		# check timestamp has microseconds part
		$values = (array) json_decode($line);
		$microseconds = $values['time'];
		$this->assertNotEquals(0, $microseconds);
		
	}


}
