<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\AccessDeniedException;
use Icewind\SMB\Exception\AlreadyExistsException;
use Icewind\SMB\Exception\AuthenticationException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\FileInUseException;
use Icewind\SMB\Exception\InvalidHostException;
use Icewind\SMB\Exception\InvalidResourceException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\NoLoginServerException;
use Icewind\SMB\Exception\NotEmptyException;
use Icewind\SMB\Exception\NotFoundException;

class Parser {
	const MSG_NOT_FOUND = 'Error opening local file ';

	/**
	 * @var \Icewind\SMB\TimeZoneProvider
	 */
	protected $timeZoneProvider;

	/**
	 * @param \Icewind\SMB\TimeZoneProvider $timeZoneProvider
	 */
	public function __construct(TimeZoneProvider $timeZoneProvider) {
		$this->timeZoneProvider = $timeZoneProvider;
	}

	private function getErrorCode($line) {
		$parts = explode(' ', $line);
		foreach ($parts as $part) {
			if (substr($part, 0, 9) === 'NT_STATUS') {
				return $part;
			}
		}
		return false;
	}

	public function checkForError($output, $path) {
		if (strpos($output[0], 'does not exist')) {
			throw new NotFoundException($path);
		}
		$error = $this->getErrorCode($output[0]);

		if (substr($output[0], 0, strlen(self::MSG_NOT_FOUND)) === self::MSG_NOT_FOUND) {
			$localPath = substr($output[0], strlen(self::MSG_NOT_FOUND));
			throw new InvalidResourceException('Failed opening local file "' . $localPath . '" for writing');
		}

		switch ($error) {
			case ErrorCodes::PathNotFound:
			case ErrorCodes::ObjectNotFound:
			case ErrorCodes::NoSuchFile:
				throw new NotFoundException($path);
			case ErrorCodes::NameCollision:
				throw new AlreadyExistsException($path);
			case ErrorCodes::AccessDenied:
				throw new AccessDeniedException($path);
			case ErrorCodes::DirectoryNotEmpty:
				throw new NotEmptyException($path);
			case ErrorCodes::FileIsADirectory:
			case ErrorCodes::NotADirectory:
				throw new InvalidTypeException($path);
			case ErrorCodes::SharingViolation:
				throw new FileInUseException($path);
			default:
				throw Exception::unknown($path, $error);
		}
	}

	/**
	 * check if the first line holds a connection failure
	 *
	 * @param $line
	 * @throws AuthenticationException
	 * @throws InvalidHostException
	 * @throws NoLoginServerException
	 */
	public function checkConnectionError($line) {
		$line = rtrim($line, ')');
		if (substr($line, -23) === ErrorCodes::LogonFailure) {
			throw new AuthenticationException('Invalid login');
		}
		if (substr($line, -26) === ErrorCodes::BadHostName) {
			throw new InvalidHostException('Invalid hostname');
		}
		if (substr($line, -22) === ErrorCodes::Unsuccessful) {
			throw new InvalidHostException('Connection unsuccessful');
		}
		if (substr($line, -28) === ErrorCodes::ConnectionRefused) {
			throw new InvalidHostException('Connection refused');
		}
		if (substr($line, -26) === ErrorCodes::NoLogonServers) {
			throw new NoLoginServerException('No login server');
		}
	}

	public function parseMode($mode) {
		$result = 0;
		$modeStrings = array(
			'R' => FileInfo::MODE_READONLY,
			'H' => FileInfo::MODE_HIDDEN,
			'S' => FileInfo::MODE_SYSTEM,
			'D' => FileInfo::MODE_DIRECTORY,
			'A' => FileInfo::MODE_ARCHIVE,
			'N' => FileInfo::MODE_NORMAL
		);
		foreach ($modeStrings as $char => $val) {
			if (strpos($mode, $char) !== false) {
				$result |= $val;
			}
		}
		return $result;
	}

	public function parseStat($output) {
		$mtime = 0;
		$mode = 0;
		$size = 0;
		foreach ($output as $line) {
			// A line = explode statement may not fill all array elements
			// properly. May happen when accessing non Windows Fileservers
			$words = explode(':', $line, 2);
			$name = isset($words[0]) ? $words[0] : '';
			$value = isset($words[1]) ? $words[1] : '';
			$value = trim($value);
			if ($name === 'write_time') {
				$mtime = strtotime($value);
			} else if ($name === 'attributes') {
				$mode = hexdec(substr($value, strpos($value, '('), -1));
			} else if ($name === 'stream') {
				list(, $size,) = explode(' ', $value);
				$size = intval($size);
			}
		}
		return array(
			'mtime' => $mtime,
			'mode' => $mode,
			'size' => $size
		);
	}

	public function parseDir($output, $basePath) {
		//last line is used space
		array_pop($output);
		$regex = '/^\s*(.*?)\s\s\s\s+(?:([NDHARS]*)\s+)?([0-9]+)\s+(.*)$/';
		//2 spaces, filename, optional type, size, date
		$content = array();
		foreach ($output as $line) {
			if (preg_match($regex, $line, $matches)) {
				list(, $name, $mode, $size, $time) = $matches;
				if ($name !== '.' and $name !== '..') {
					$mode = $this->parseMode($mode);
					$time = strtotime($time . ' ' . $this->timeZoneProvider->get());
					$content[] = new FileInfo($basePath . '/' . $name, $name, $size, $time, $mode);
				}
			}
		}
		return $content;
	}

	public function parseListShares($output) {
		$shareNames = array();
		foreach ($output as $line) {
			if (strpos($line, '|')) {
				list($type, $name, $description) = explode('|', $line);
				if (strtolower($type) === 'disk') {
					$shareNames[$name] = $description;
				}
			}
		}
		return $shareNames;
	}
}
