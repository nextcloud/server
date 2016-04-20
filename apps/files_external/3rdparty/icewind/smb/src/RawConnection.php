<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

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
	 */
	private $pipes;

	/**
	 * @var resource $process
	 */
	private $process;

	public function __construct($command, $env = array()) {
		$this->command = $command;
		$this->env = $env;
		$this->connect();
	}

	private function connect() {
		$descriptorSpec = array(
			0 => array('pipe', 'r'), // child reads from stdin
			1 => array('pipe', 'w'), // child writes to stdout
			2 => array('pipe', 'w'), // child writes to stderr
			3 => array('pipe', 'r'), // child reads from fd#3
			4 => array('pipe', 'r'), // child reads from fd#4
			5 => array('pipe', 'w')  // child writes to fd#5
		);
		setlocale(LC_ALL, Server::LOCALE);
		$env = array_merge($this->env, array(
			'CLI_FORCE_INTERACTIVE' => 'y', // Needed or the prompt isn't displayed!!
			'LC_ALL' => Server::LOCALE,
			'LANG' => Server::LOCALE,
			'COLUMNS' => 8192 // prevent smbclient from line-wrapping it's output
		));
		$this->process = proc_open($this->command, $descriptorSpec, $this->pipes, '/', $env);
		if (!$this->isValid()) {
			throw new ConnectionException();
		}
	}

	/**
	 * check if the connection is still active
	 *
	 * @return bool
	 */
	public function isValid() {
		if (is_resource($this->process)) {
			$status = proc_get_status($this->process);
			return $status['running'];
		} else {
			return false;
		}
	}

	/**
	 * send input to the process
	 *
	 * @param string $input
	 */
	public function write($input) {
		fwrite($this->getInputStream(), $input);
		fflush($this->getInputStream());
	}

	/**
	 * read a line of output
	 *
	 * @return string
	 */
	public function readLine() {
		return stream_get_line($this->getOutputStream(), 4086, "\n");
	}

	/**
	 * read a line of output
	 *
	 * @return string
	 */
	public function readError() {
		return trim(stream_get_line($this->getErrorStream(), 4086));
	}

	/**
	 * get all output until the process closes
	 *
	 * @return array
	 */
	public function readAll() {
		$output = array();
		while ($line = $this->readLine()) {
			$output[] = $line;
		}
		return $output;
	}

	public function getInputStream() {
		return $this->pipes[0];
	}

	public function getOutputStream() {
		return $this->pipes[1];
	}

	public function getErrorStream() {
		return $this->pipes[2];
	}

	public function getAuthStream() {
		return $this->pipes[3];
	}

	public function getFileInputStream() {
		return $this->pipes[4];
	}

	public function getFileOutputStream() {
		return $this->pipes[5];
	}

	public function writeAuthentication($user, $password) {
		$auth = ($password === false)
			? "username=$user"
			: "username=$user\npassword=$password";

		if (fwrite($this->getAuthStream(), $auth) === false) {
			fclose($this->getAuthStream());
			return false;
		}
		fclose($this->getAuthStream());
		return true;
	}

	public function close($terminate = true) {
		if (!is_resource($this->process)) {
			return;
		}
		if ($terminate) {
			// if for case that posix_ functions are not available
			if (function_exists('posix_kill')) {
				$status = proc_get_status($this->process);
				$ppid = $status['pid'];
				$pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
				foreach($pids as $pid) {
					if(is_numeric($pid)) {
						//9 is the SIGKILL signal
						posix_kill($pid, 9);
					}
				}
			}
			proc_terminate($this->process);
		}
		proc_close($this->process);
	}

	public function reconnect() {
		$this->close();
		$this->connect();
	}

	public function __destruct() {
		$this->close();
	}
}
