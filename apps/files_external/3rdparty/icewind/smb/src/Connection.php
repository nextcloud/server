<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\AuthenticationException;
use Icewind\SMB\Exception\ConnectionException;
use Icewind\SMB\Exception\InvalidHostException;

class Connection extends RawConnection {
	const DELIMITER = 'smb:';

	/**
	 * send input to smbclient
	 *
	 * @param string $input
	 */
	public function write($input) {
		parent::write($input . PHP_EOL);
	}

	/**
	 * get all unprocessed output from smbclient until the next prompt
	 *
	 * @throws ConnectionException
	 * @return string
	 */
	public function read() {
		if (!$this->isValid()) {
			throw new ConnectionException();
		}
		$line = $this->readLine(); //first line is prompt
		$this->checkConnectionError($line);

		$output = array();
		$line = $this->readLine();
		$length = mb_strlen(self::DELIMITER);
		while (mb_substr($line, 0, $length) !== self::DELIMITER) { //next prompt functions as delimiter
			$output[] .= $line;
			$line = $this->readLine();
		}
		return $output;
	}

	/**
	 * check if the first line holds a connection failure
	 *
	 * @param $line
	 * @throws AuthenticationException
	 * @throws InvalidHostException
	 */
	private function checkConnectionError($line) {
		$line = rtrim($line, ')');
		if (substr($line, -23) === ErrorCodes::LogonFailure) {
			throw new AuthenticationException();
		}
		if (substr($line, -26) === ErrorCodes::BadHostName) {
			throw new InvalidHostException();
		}
		if (substr($line, -22) === ErrorCodes::Unsuccessful) {
			throw new InvalidHostException();
		}
		if (substr($line, -28) === ErrorCodes::ConnectionRefused) {
			throw new InvalidHostException();
		}
	}

	public function close($terminate = true) {
		if (is_resource($this->getInputStream())) {
			$this->write('close' . PHP_EOL);
		}
		parent::close($terminate);
	}
}
