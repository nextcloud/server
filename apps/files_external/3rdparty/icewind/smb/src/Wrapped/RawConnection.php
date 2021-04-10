<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Wrapped;

use Icewind\SMB\Exception\ConnectException;
use Icewind\SMB\Exception\ConnectionException;

class RawConnection {
	/**
	 * @var string
	 */
	private $command;

	/**
	 * @var string[]
	 */
	private $env;

	/**
	 * @var resource[] $pipes
	 *
	 * $pipes[0] holds STDIN for smbclient
	 * $pipes[1] holds STDOUT for smbclient
	 * $pipes[3] holds the authfile for smbclient
	 * $pipes[4] holds the stream for writing files
	 * $pipes[5] holds the stream for reading files
	 */
	private $pipes = [];

	/**
	 * @var resource|null $process
	 */
	private $process;

	/**
	 * @var resource|null $authStream
	 */
	private $authStream = null;

	/**
	 * @param string $command
	 * @param array<string, string> $env
	 */
	public function __construct(string $command, array $env = []) {
		$this->command = $command;
		$this->env = $env;
	}

	/**
	 * @throws ConnectException
	 * @psalm-assert resource $this->process
	 */
	public function connect(): void {
		if (is_null($this->getAuthStream())) {
			throw new ConnectException('Authentication not set before connecting');
		}

		$descriptorSpec = [
			0 => ['pipe', 'r'], // child reads from stdin
			1 => ['pipe', 'w'], // child writes to stdout
			2 => ['pipe', 'w'], // child writes to stderr
			3 => $this->getAuthStream(), // child reads from fd#3
			4 => ['pipe', 'r'], // child reads from fd#4
			5 => ['pipe', 'w']  // child writes to fd#5
		];

		setlocale(LC_ALL, Server::LOCALE);
		$env = array_merge($this->env, [
			'CLI_FORCE_INTERACTIVE' => 'y', // Needed or the prompt isn't displayed!!
			'LC_ALL'                => Server::LOCALE,
			'LANG'                  => Server::LOCALE,
			'COLUMNS'               => 8192 // prevent smbclient from line-wrapping it's output
		]);
		$this->process = proc_open($this->command, $descriptorSpec, $this->pipes, '/', $env);
		if (!$this->isValid()) {
			throw new ConnectionException();
		}
	}

	/**
	 * check if the connection is still active
	 *
	 * @return bool
	 * @psalm-assert-if-true resource $this->process
	 */
	public function isValid(): bool {
		if (is_resource($this->process)) {
			$status = proc_get_status($this->process);
			return (bool)$status['running'];
		} else {
			return false;
		}
	}

	/**
	 * send input to the process
	 *
	 * @param string $input
	 * @return int|bool
	 */
	public function write(string $input) {
		$result = @fwrite($this->getInputStream(), $input);
		fflush($this->getInputStream());
		return $result;
	}

	/**
	 * read a line of output
	 *
	 * @return string|false
	 */
	public function readLine() {
		return stream_get_line($this->getOutputStream(), 4086, "\n");
	}

	/**
	 * read a line of output
	 *
	 * @return string|false
	 */
	public function readError() {
		$line = stream_get_line($this->getErrorStream(), 4086);
		return $line !== false ? trim($line) : false;
	}

	/**
	 * get all output until the process closes
	 *
	 * @return string[]
	 */
	public function readAll(): array {
		$output = [];
		while ($line = $this->readLine()) {
			$output[] = $line;
		}
		return $output;
	}

	/**
	 * @return resource
	 */
	public function getInputStream() {
		return $this->pipes[0];
	}

	/**
	 * @return resource
	 */
	public function getOutputStream() {
		return $this->pipes[1];
	}

	/**
	 * @return resource
	 */
	public function getErrorStream() {
		return $this->pipes[2];
	}

	/**
	 * @return resource|null
	 */
	public function getAuthStream() {
		return $this->authStream;
	}

	/**
	 * @return resource
	 */
	public function getFileInputStream() {
		return $this->pipes[4];
	}

	/**
	 * @return resource
	 */
	public function getFileOutputStream() {
		return $this->pipes[5];
	}

	/**
	 * @param string|null $user
	 * @param string|null $password
	 * @psalm-assert resource $this->authStream
	 */
	public function writeAuthentication(?string $user, ?string $password): void {
		$auth = ($password === null)
			? "username=$user"
			: "username=$user\npassword=$password\n";

		$this->authStream = fopen('php://temp', 'w+');
		fwrite($this->authStream, $auth);
	}

	/**
	 * @param bool $terminate
	 * @psalm-assert null $this->process
	 */
	public function close(bool $terminate = true): void {
		if (!is_resource($this->process)) {
			return;
		}
		if ($terminate) {
			proc_terminate($this->process);
		}
		proc_close($this->process);
		$this->process = null;
	}

	public function reconnect(): void {
		$this->close();
		$this->connect();
	}

	public function __destruct() {
		$this->close();
	}
}
