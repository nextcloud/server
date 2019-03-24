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

namespace OC\Log;


use OCP\IConfig;
use OCP\ILogger;
use OCP\Log\IWriter;

class Graylog implements IWriter {

	private static $VERSION = '1.1';
	private $host;
	private $target;
	private $port;
	private $protocol;

	protected static $LEVELS = [
		ILogger::FATAL => LOG_CRIT,
		ILogger::ERROR => LOG_ERR,
		ILogger::WARN => LOG_WARNING,
		ILogger::INFO => LOG_INFO,
		ILogger::DEBUG => LOG_DEBUG
	];

	public function __construct(IConfig $config) {
		$this->host = gethostname();
		$this->protocol = $config->getSystemValue('graylog_method', 'udp');
		$address = $config->getSystemValue('graylog_host', '');
		if (false !== strpos($address, ':')) {
			$this->target = explode(':', $address)[0];
			$this->port = intval(explode(':', $address)[1]);
		} else {
			$this->target = $address;
			$this->port = 514;
		}
	}

	/**
	 * sena a message to the Graylog server
	 *
	 * @param string $app
	 * @param string $message
	 * @param int $level
	 */
	public function write(string $app, $message, int $level) {
		$chunks = [];
		$msg = '{"version":"' . self::$VERSION . '","host":"' .
			$this->host . '","short_message":"' .
			str_replace("\n", '\\n', '{' . $app . '} ' . $message) .
			'","level":"' . self::$LEVELS[$level] . '","timestamp":' .
			time() . '}';
		switch ($this->protocol) {
			case 'udp':
				$chunks = str_split($msg, 8000);
				break;
			case 'tcp':
				$chunks[0] = $msg;
				break;
		}
		$count = count($chunks);
		$errno = 0;
		$errstr = '';
		$fp = stream_socket_client(
			$this->protocol . '://' . $this->target . ':' . $this->port,
			$errno,
			$errstr,
			5
		);
		switch ($count > 1) {
			case true:
				$id = random_bytes(8);
				for ($i = 0; $i < $count; $i++) {
					fwrite($fp, pack('n', 0x1e0f) . $id . $i . $count .
						$chunks[$i] . pack('x'));
				}
				break;
			case false:
				fwrite($fp, $chunks[0]);
				break;
		}
		fclose($fp);
	}
}
