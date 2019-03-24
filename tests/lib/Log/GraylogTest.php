<?php
/**
 *
 * @copyright Copyright (c) <2019>, <Thomas Pulzer> (t.pulzer@thesecretgamer.de)
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

namespace OC\tests\lib\Log;


use OC\Log\Graylog;
use OC\SystemConfig;
use Test\TestCase;

class GraylogTest extends TestCase {

	/** @var string */
	private $graylog_method_restore;
	/** @var string */
	private $graylog_host_restore;
	/** @var  SystemConfig */
	private $config;
	/** @var string */
	private $buf;
	/** @var string */
	private $from;
	/** @var integer */
	private $port;

	protected function setUp() {
		parent::setUp();
		$this->config = \OC::$server->getSystemConfig();
		$this->graylog_method_restore = $this->config->getValue('graylog_method');
		$this->graylog_host_restore = $this->config->getValue('graylog_host');
		$this->buf = '';
		$this->from = '';
		$this->port = 0;
	}

	public function testUnchunkedUdp() {
		$this->config->setValue('graylog_method', 'udp');
		$this->config->setValue('graylog_host', '127.0.0.1:5140');

		// Create a mock server to send a test message to
		$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($s, '127.0.0.1', 5140);
		socket_set_nonblock($s);

		$graylog = new Graylog(\OC::$server->getConfig());
		$graylog->write('GraylogTest', 'UDP Graylog test < 8kb', 1);

		socket_recvfrom($s, $this->buf, 8000, 0, $this->from, $this->port);
		socket_close($s);

		// The resulting GELF message has to be 130 characters long
		$this->assertEquals(130, strlen($this->buf));
	}

	public function testChunkedUdp() {
		$this->config->setValue('graylog_method', 'udp');
		$this->config->setValue('graylog_host', '127.0.0.1:5140');

		// Create a mock server to send a test message to
		$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($s, '127.0.0.1', 5140);
		socket_set_nonblock($s);

		$graylog = new Graylog(\OC::$server->getConfig());
		$msg = "Very log message filled with garbage to execeed 8kb limit. ";
		for($i = 0; $i < 8000; $i++) {
		  $msg .= "A";
		}
		$graylog->write('GraylogTest', $msg, 3);

		socket_recvfrom($s, $this->buf, 8000, 0, $this->from, $this->port);
		socket_close($s);

		// The first response should start with 0x1E 0x0F, has sequence 0
		// at position 10 and total count 2 at position 11
		$this->assertEquals(0x1e0f, unpack('n', $this->buf)[1]);
		$this->assertEquals(0, intval(substr($this->buf, 10, 1)));
		$this->assertEquals(2, intval(substr($this->buf, 11, 1)));
	}

	protected function tearDown() {
		if (isset($this->graylog_method_restore)) {
			$this->config->setValue('graylog_method', $this->graylog_method_restore);
		} else {
			$this->config->deleteValue('graylog_method');
		}
		if (isset($this->graylog_host_restore)) {
			$this->config->setValue('graylog_host', $this->graylog_host_restore);
		} else {
			$this->config->deleteValue('graylog_host');
		}
		parent::tearDown();
	}

}
