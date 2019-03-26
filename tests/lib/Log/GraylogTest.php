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
	private $protocol_restore;
	/** @var string */
	private $target_restore;
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
		$this->protocol_restore = $this->config->getValue('graylog_proto');
		$this->target_restore = $this->config->getValue('graylog_host');
		$this->buf = '';
		$this->from = '';
		$this->port = 0;
	}

	public function testUnchunkedUdp() {
		$this->config->setValue('graylog_proto', 'udp');
		$this->config->setValue('graylog_host', '127.0.0.1:5140');

		// Create a mock server to send a test message to
		$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($s, '127.0.0.1', 5140);
		socket_set_nonblock($s);

		$id = 'GraylogTest';
		$msg = 'UDP Graylog test < 1kb';
		$graylog = new Graylog(\OC::$server->getConfig());
		$graylog->write('GraylogTest', $msg, 1);

		socket_recvfrom($s, $this->buf, 1025, 0, $this->from, $this->port);
		socket_close($s);

		// The resulting GELF message has a length of 79 + length of host name +
		// length of app name + length of log message + 3 formatting characters.
		$expected = 79 + strlen(gethostname()) + strlen($msg) + strlen($id) + 3;
		$this->assertEquals($expected, strlen($this->buf));
	}

	public function testChunkedUdp() {
		$this->config->setValue('graylog_proto', 'udp');
		$this->config->setValue('graylog_host', '127.0.0.1:5140');

		// Create a mock server to send a test message to
		$s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($s, '127.0.0.1', 5140);
		socket_set_nonblock($s);

		$id = 'GraylogTest';
		$msg = "Very log message filled with garbage to exceed 1kb limit. ";
		for($i = 0; $i < 1024; $i++) {
			$msg .= "A";
		}
		$graylog = new Graylog(\OC::$server->getConfig());
		$graylog->write($id, $msg, 3);

		socket_recvfrom($s, $this->buf, 1034, 0, $this->from, $this->port);
		socket_close($s);

		// The chunked GELF message must start start with 0x1E 0x0F, followed
		// by 8 byte message id, 1 byte current sequence and 1 byte total chunk
		// count. In this test the total chunk count is 2 and we examine the
		// first chunk (zero-indexed).
		$this->assertEquals(0x1e0f, unpack('n', $this->buf)[1]);
		$this->assertEquals(0, unpack('C', substr($this->buf, 10, 1))[1]);
		$this->assertEquals(2, unpack('C', substr($this->buf, 11, 1))[1]);
	}

	public function testTcp() {
		$this->config->setValue('graylog_proto', 'tcp');
		$this->config->setValue('graylog_host', '127.0.0.1:5140');

		// Create a mock server to send a test message to
		$s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_bind($s, '127.0.0.1', 5140);
		socket_listen($s);
		socket_set_nonblock($s);

		$id = 'GraylogTest';
		$msg = 'TCP Graylog test < 1kb';
		$graylog = new Graylog(\OC::$server->getConfig());
		$graylog->write($id, $msg, 3);

		$c = socket_accept($s);
		$this->buf = socket_read($c, 1025);
		socket_close($c);
		socket_close($s);

		// The resulting GELF message has a length of 79 + length of host name +
		// length of app name + length of log message + 3 formatting characters.
		$expected = 79 + strlen(gethostname()) + strlen($msg) + strlen($id) + 3;
		$this->assertEquals($expected, strlen($this->buf));
	}

	protected function tearDown() {
		if (isset($this->protocol_restore)) {
			$this->config->setValue('graylog_proto', $this->protocol_restore);
		} else {
			$this->config->deleteValue('graylog_proto');
		}
		if (isset($this->target_restore)) {
			$this->config->setValue('graylog_host', $this->target_restore);
		} else {
			$this->config->deleteValue('graylog_host');
		}
		parent::tearDown();
	}

}
