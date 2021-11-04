<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Wrapped;

use Icewind\SMB\Exception\AccessDeniedException;
use Icewind\SMB\Exception\AuthenticationException;
use Icewind\SMB\Exception\ConnectException;
use Icewind\SMB\Exception\ConnectionException;
use Icewind\SMB\Exception\ConnectionRefusedException;
use Icewind\SMB\Exception\InvalidHostException;
use Icewind\SMB\Exception\NoLoginServerException;

class Connection extends RawConnection {
	const DELIMITER = 'smb:';
	const DELIMITER_LENGTH = 4;

	/** @var Parser */
	private $parser;

	/**
	 * @param string $command
	 * @param Parser $parser
	 * @param array<string, string> $env
	 */
	public function __construct(string $command, Parser $parser, array $env = []) {
		parent::__construct($command, $env);
		$this->parser = $parser;
	}

	/**
	 * send input to smbclient
	 *
	 * @param string $input
	 */
	public function write(string $input) {
		return parent::write($input . PHP_EOL);
	}

	/**
	 * @throws ConnectException
	 */
	public function clearTillPrompt(): void {
		$this->write('');
		do {
			$promptLine = $this->readTillPrompt();
			if ($promptLine === false) {
				break;
			}
			$this->parser->checkConnectionError($promptLine);
		} while (!$this->isPrompt($promptLine));
		if ($this->write('') === false) {
			throw new ConnectionRefusedException();
		}
		$this->readTillPrompt();
	}

	/**
	 * get all unprocessed output from smbclient until the next prompt
	 *
	 * @return string[]
	 * @throws AuthenticationException
	 * @throws ConnectException
	 * @throws ConnectionException
	 * @throws InvalidHostException
	 * @throws NoLoginServerException
	 * @throws AccessDeniedException
	 */
	public function read(): array {
		if (!$this->isValid()) {
			throw new ConnectionException('Connection not valid');
		}
		$output = $this->readTillPrompt();
		if ($output === false) {
			$this->unknownError(false);
		}
		$output = explode("\n", $output);
		// last line contains the prompt
		array_pop($output);
		return $output;
	}

	private function isPrompt(string $line): bool {
		return substr($line, 0, self::DELIMITER_LENGTH) === self::DELIMITER;
	}

	/**
	 * @param string|bool $promptLine (optional) prompt line that might contain some info about the error
	 * @throws ConnectException
	 * @return no-return
	 */
	private function unknownError($promptLine = '') {
		if ($promptLine) { //maybe we have some error we missed on the previous line
			throw new ConnectException('Unknown error (' . $promptLine . ')');
		} else {
			$error = $this->readError(); // maybe something on stderr
			if ($error) {
				throw new ConnectException('Unknown error (' . $error . ')');
			} else {
				throw new ConnectException('Unknown error');
			}
		}
	}

	public function close(bool $terminate = true): void {
		if (get_resource_type($this->getInputStream()) === 'stream') {
			// ignore any errors while trying to send the close command, the process might already be dead
			@$this->write('close' . PHP_EOL);
		}
		$this->close_process($terminate);
	}
}
