<?php

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

namespace NextcloudServerControl {

class SocketException extends \Exception {
	public function __construct($message) {
		parent::__construct($message);
	}
}

/**
 * Common class for communication between client and server.
 *
 * Clients and server communicate through messages: a client sends a request and
 * the server answers with a response. Requests and responses all have the same
 * common structure composed by a mandatory header and optional data. The header
 * contains a code that identifies the type of request or response followed by
 * the length of the data (which can be 0). The data is a free form string that
 * depends on each request and response type.
 *
 * The Messenger abstracts all that and provides two public methods: readMessage
 * and writeMessage. For each connection a client first writes the request
 * message and then reads the response message, while the server first reads the
 * request message and then writes the response message. If the client needs to
 * send another request it must connect again to the server.
 *
 * The Messenger class in the server must be kept in sync with the Messenger
 * class in the client. Due to the size of the code and its current use it was
 * more practical, at least for the time being, to keep two copies of the code
 * than creating a library that had to be downloaded and included in the client
 * and in the server.
 */
class Messenger {

	/**
	 * Reset the Nextcloud server.
	 *
	 * -Request data: empty
	 * -OK response data: empty.
	 * -Failed response data: error information.
	 */
	const CODE_REQUEST_RESET = 0;

	const CODE_RESPONSE_OK = 0;
	const CODE_RESPONSE_FAILED = 1;

	const HEADER_LENGTH = 5;

	/**
	 * Reads a message from the given socket.
	 *
	 * The message is returned as an indexed array with keys "code" and "data".
	 *
	 * @param resource $socket the socket to read the message from.
	 * @return array the message read.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	public static function readMessage($socket) {
		$header = self::readSocket($socket, self::HEADER_LENGTH);
		$header = unpack("Ccode/VdataLength", $header);

		$data = self::readSocket($socket, $header["dataLength"]);

		return [ "code" => $header["code"], "data" => $data ];
	}

	/**
	 * Reads content from the given socket.
	 *
	 * It blocks until the specified number of bytes were read.
	 *
	 * @param resource $socket the socket to read the message from.
	 * @param int $length the number of bytes to read.
	 * @return string the content read.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	private static function readSocket($socket, $length) {
		if ($socket == null) {
			throw new SocketException("Null socket can not be read from");
		}

		$pendingLength = $length;
		$content = "";

		while ($pendingLength > 0) {
			$readContent = socket_read($socket, $pendingLength);
			if ($readContent === "") {
				throw new SocketException("Socket could not be read: $pendingLength bytes are pending, but there is no more data to read");
			} else if ($readContent == false) {
				throw new SocketException("Socket could not be read: " . socket_strerror(socket_last_error()));
			}

			$pendingLength -= strlen($readContent);
			$content = $content . $readContent;
		}

		return $content;
	}

	/**
	 * Writes a message to the given socket.
	 *
	 * @param resource $socket the socket to write the message to.
	 * @param int $code the message code.
	 * @param string $data the message data, if any.
	 * @throws SocketException if an error occurs while reading the socket.
	 */
	public static function writeMessage($socket, $code, $data = "") {
		if ($socket == null) {
			throw new SocketException("Null socket can not be written to");
		}

		$header = pack("CV", $code, strlen($data));

		$message = $header . $data;
		$pendingLength = strlen($message);

		while ($pendingLength > 0) {
			$sent = socket_write($socket, $message, $pendingLength);
			if ($sent !== 0 && $sent == false) {
				throw new SocketException("Message ($message) could not be written: " . socket_strerror(socket_last_error()));
			}

			$pendingLength -= $sent;
			$message = substr($message, $sent);
		}
	}
}

}

namespace {

use NextcloudServerControl\Messenger;
use NextcloudServerControl\SocketException;

/**
 * Helper to manage the Nextcloud test server running in a Drone service.
 *
 * The NextcloudTestServerDroneHelper controls a Nextcloud test server running
 * in a Drone service. The "setUp" method resets the Nextcloud server to its
 * initial state; nothing needs to be done in the "cleanUp" method. To be able
 * to control the remote Nextcloud server the Drone service must provide the
 * Nextcloud server control server; the port in which the server listens on can
 * be set with the $nextcloudTestServerControlPort parameter of the constructor.
 *
 * Drone services are available at "127.0.0.1", so the Nextcloud server is
 * expected to see "127.0.0.1" as a trusted domain (which would be the case if
 * it was installed by running "occ maintenance:install"). Note, however, that
 * the Nextcloud server does not listen on port "80" but on port "8000" due to
 * internal issues of the Nextcloud server control. In any case, the base URL to
 * access the Nextcloud server can be got from "getBaseUrl".
 */
class NextcloudTestServerDroneHelper implements NextcloudTestServerHelper {

	/**
	 * @var int
	 */
	private $nextcloudTestServerControlPort;

	/**
	 * Creates a new NextcloudTestServerDroneHelper.
	 *
	 * @param int $nextcloudTestServerControlPort the port in which the
	 *        Nextcloud server control is listening.
	 */
	public function __construct($nextcloudTestServerControlPort) {
		$this->nextcloudTestServerControlPort = $nextcloudTestServerControlPort;
	}

	/**
	 * Sets up the Nextcloud test server.
	 *
	 * It resets the Nextcloud test server through the control system provided
	 * by its Drone service and waits for the Nextcloud test server to be
	 * started again; if the server can not be reset or if it does not start
	 * again after some time an exception is thrown (as it is just a warning for
	 * the test runner and nothing to be explicitly catched a plain base
	 * Exception is used).
	 *
	 * @throws \Exception if the Nextcloud test server in the Drone service can
	 *         not be reset or started again.
	 */
	public function setUp() {
		$resetNextcloudServerCallback = function($socket) {
			Messenger::writeMessage($socket, Messenger::CODE_REQUEST_RESET);

			$response = Messenger::readMessage($socket);

			if ($response["code"] == Messenger::CODE_RESPONSE_FAILED) {
				throw new Exception("Request to reset Nextcloud server failed: " . $response["data"]);
			}
		};
		$this->sendRequestAndHandleResponse($resetNextcloudServerCallback);

		$timeout = 60;
		if (!Utils::waitForServer($this->getBaseUrl(), $timeout)) {
			throw new Exception("Nextcloud test server could not be started");
		}
	}

	/**
	 * Cleans up the Nextcloud test server.
	 *
	 * Nothing needs to be done when using the Drone service.
	 */
	public function cleanUp() {
	}

	/**
	 * Returns the base URL of the Nextcloud test server.
	 *
	 * @return string the base URL of the Nextcloud test server.
	 */
	public function getBaseUrl() {
		return "http://127.0.0.1:8000/index.php";
	}

	/**
	 * Executes the given callback to communicate with the Nextcloud test server
	 * control.
	 *
	 * A socket is created with the Nextcloud test server control and passed to
	 * the callback to send the request and handle its response.
	 *
	 * @param \Closure $nextcloudServerControlCallback the callback to call with
	 *        the communication socket.
	 * @throws \Exception if any socket-related operation fails.
	 */
	private function sendRequestAndHandleResponse($nextcloudServerControlCallback) {
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			throw new Exception("Request socket to reset Nextcloud server could not be created: " . socket_strerror(socket_last_error()));
		}

		try {
			if (socket_connect($socket, "127.0.0.1", $this->nextcloudTestServerControlPort) === false) {
				throw new Exception("Request socket to reset Nextcloud server could not be connected: " . socket_strerror(socket_last_error()));
			}

			$nextcloudServerControlCallback($socket);
		} catch (SocketException $exception) {
			throw new Exception("Request socket to reset Nextcloud server failed: " . $exception->getMessage());
		} finally {
			socket_close($socket);
		}
	}

}

}
