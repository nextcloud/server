<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

class FileInfo implements IFileInfo {
	/*
	 * Mappings of the DOS mode bits, as returned by smbc_getxattr() when the
	 * attribute name "system.dos_attr.mode" (or "system.dos_attr.*" or
	 * "system.*") is specified.
	 */
	const MODE_READONLY = 0x01;
	const MODE_HIDDEN = 0x02;
	const MODE_SYSTEM = 0x04;
	const MODE_VOLUME_ID = 0x08;
	const MODE_DIRECTORY = 0x10;
	const MODE_ARCHIVE = 0x20;
	const MODE_NORMAL = 0x80;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var int
	 */
	protected $size;

	/**
	 * @var int
	 */
	protected $time;

	/**
	 * @var int
	 */
	protected $mode;

	/**
	 * @param string $path
	 * @param string $name
	 * @param int $size
	 * @param int $time
	 * @param int $mode
	 */
	public function __construct($path, $name, $size, $time, $mode) {
		$this->path = $path;
		$this->name = $name;
		$this->size = $size;
		$this->time = $time;
		$this->mode = $mode;
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
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		return $this->time;
	}

	/**
	 * @return bool
	 */
	public function isDirectory() {
		return (bool)($this->mode & self::MODE_DIRECTORY);
	}

	/**
	 * @return bool
	 */
	public function isReadOnly() {
		return (bool)($this->mode & self::MODE_READONLY);
	}

	/**
	 * @return bool
	 */
	public function isHidden() {
		return (bool)($this->mode & self::MODE_HIDDEN);
	}

	/**
	 * @return bool
	 */
	public function isSystem() {
		return (bool)($this->mode & self::MODE_SYSTEM);
	}

	/**
	 * @return bool
	 */
	public function isArchived() {
		return (bool)($this->mode & self::MODE_ARCHIVE);
	}
}
