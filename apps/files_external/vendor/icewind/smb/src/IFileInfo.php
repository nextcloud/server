<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

interface IFileInfo {
	/**
	 * @return string
	 */
	public function getPath();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return int
	 */
	public function getSize();

	/**
	 * @return int
	 */
	public function getMTime();

	/**
	 * @return bool
	 */
	public function isDirectory();

	/**
	 * @return bool
	 */
	public function isReadOnly();

	/**
	 * @return bool
	 */
	public function isHidden();

	/**
	 * @return bool
	 */
	public function isSystem();

	/**
	 * @return bool
	 */
	public function isArchived();
}
