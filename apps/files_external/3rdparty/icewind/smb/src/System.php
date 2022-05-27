<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\Exception;

class System implements ISystem {
	/** @var (string|null)[] */
	private $paths = [];

	/**
	 * Get the path to a file descriptor of the current process
	 *
	 * @param int $num the file descriptor id
	 * @return string
	 * @throws Exception
	 */
	public function getFD(int $num): string {
		$folders = [
			'/proc/self/fd',
			'/dev/fd'
		];
		foreach ($folders as $folder) {
			if (file_exists($folder)) {
				return $folder . '/' . $num;
			}
		}
		throw new Exception('Cant find file descriptor path');
	}

	public function getSmbclientPath(): ?string {
		return $this->getBinaryPath('smbclient');
	}

	public function getNetPath(): ?string {
		return $this->getBinaryPath('net');
	}

	public function getSmbcAclsPath(): ?string {
		return $this->getBinaryPath('smbcacls');
	}

	public function getStdBufPath(): ?string {
		return $this->getBinaryPath('stdbuf');
	}

	public function getDatePath(): ?string {
		return $this->getBinaryPath('date');
	}

	public function libSmbclientAvailable(): bool {
		return function_exists('smbclient_state_new');
	}

	protected function getBinaryPath(string $binary): ?string {
		if (!isset($this->paths[$binary])) {
			$result = null;
			$output = [];
			exec("which $binary 2>&1", $output, $result);

			if ($result === 0 && isset($output[0])) {
				$this->paths[$binary] = (string)$output[0];
			} else if (is_executable("/usr/bin/$binary")) {
				$this->paths[$binary] = "/usr/bin/$binary";
			} else {
				$this->paths[$binary] = null;
			}
		}
		return $this->paths[$binary];
	}
}
