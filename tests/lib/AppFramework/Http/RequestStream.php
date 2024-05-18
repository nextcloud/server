<?php

namespace Test\AppFramework\Http;

/**
 * Copy of http://dk1.php.net/manual/en/stream.streamwrapper.example-1.php
 * Used to simulate php://input for Request tests
 */
class RequestStream {
	protected int $position = 0;
	protected string $varname = '';
	/* @var resource */
	public $context;

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool {
		$url = parse_url($path);
		$this->varname = $url["host"] ?? '';
		$this->position = 0;

		return true;
	}

	public function stream_read(int $count): string {
		$ret = substr($GLOBALS[$this->varname], $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	public function stream_write(string $data): int {
		$left = substr($GLOBALS[$this->varname], 0, $this->position);
		$right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
		$GLOBALS[$this->varname] = $left . $data . $right;
		$this->position += strlen($data);
		return strlen($data);
	}

	public function stream_tell(): int {
		return $this->position;
	}

	public function stream_eof(): bool {
		return $this->position >= strlen($GLOBALS[$this->varname]);
	}

	public function stream_seek(int $offset, int $whence = SEEK_SET): bool {
		switch ($whence) {
			case SEEK_SET:
				if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
					$this->position = $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_CUR:
				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_END:
				if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
					$this->position = strlen($GLOBALS[$this->varname]) + $offset;
					return true;
				} else {
					return false;
				}
				break;

			default:
				return false;
		}
	}

	public function stream_stat(): array {
		$size = strlen($GLOBALS[$this->varname]);
		$time = time();
		$data = [
			'dev' => 0,
			'ino' => 0,
			'mode' => 0777,
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => '',
			'size' => $size,
			'atime' => $time,
			'mtime' => $time,
			'ctime' => $time,
			'blksize' => -1,
			'blocks' => -1,
		];
		return array_values($data) + $data;
		//return false;
	}

	public function stream_metadata(string $path, int $option, $var): bool {
		if ($option == STREAM_META_TOUCH) {
			$url = parse_url($path);
			$varname = $url["host"] ?? '';
			if (!isset($GLOBALS[$varname])) {
				$GLOBALS[$varname] = '';
			}
			return true;
		}
		return false;
	}
}
