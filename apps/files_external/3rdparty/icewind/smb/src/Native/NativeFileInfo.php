<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\IFileInfo;

class NativeFileInfo implements IFileInfo {
	const MODE_FILE = 0100000;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var NativeShare
	 */
	protected $share;

	/**
	 * @var array|null
	 */
	protected $statCache = null;

	/** @var callable|null */
	protected $statCallback = null;

	/**
	 * @var int
	 */
	protected $modeCache;

	/**
	 * @param NativeShare $share
	 * @param string $path
	 * @param string $name
	 * @param array|callable $stat
	 */
	public function __construct($share, $path, $name, $stat) {
		$this->share = $share;
		$this->path = $path;
		$this->name = $name;

		if (is_array($stat)) {
			$this->statCache = $stat;
		} elseif (is_callable($stat)) {
			$this->statCallback = $stat;
		} else {
			throw new \InvalidArgumentException('$stat needs to be an array or callback');
		}
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return array
	 */
	protected function stat() {
		if (is_null($this->statCache)) {
			$this->statCache = call_user_func($this->statCallback);
		}
		return $this->statCache;
	}

	/**
	 * @return int
	 */
	public function getSize() {
		$stat = $this->stat();
		return $stat['size'];
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		$stat = $this->stat();
		return $stat['mtime'];
	}

	/**
	 * @return bool
	 */
	public function isDirectory() {
		$stat = $this->stat();
		return !($stat['mode'] & self::MODE_FILE);
	}

	/**
	 * @return int
	 */
	protected function getMode() {
		if (!$this->modeCache) {
			$attribute = $this->share->getAttribute($this->path, 'system.dos_attr.mode');
			// parse hex string
			$this->modeCache = (int)hexdec(substr($attribute, 2));
		}
		return $this->modeCache;
	}

	/**
	 * @return bool
	 */
	public function isReadOnly() {
		$mode = $this->getMode();
		return (bool)($mode & IFileInfo::MODE_READONLY);
	}

	/**
	 * @return bool
	 */
	public function isHidden() {
		$mode = $this->getMode();
		return (bool)($mode & IFileInfo::MODE_HIDDEN);
	}

	/**
	 * @return bool
	 */
	public function isSystem() {
		$mode = $this->getMode();
		return (bool)($mode & IFileInfo::MODE_SYSTEM);
	}

	/**
	 * @return bool
	 */
	public function isArchived() {
		$mode = $this->getMode();
		return (bool)($mode & IFileInfo::MODE_ARCHIVE);
	}
}
