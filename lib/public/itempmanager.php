<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface ITempManager {
	/**
	 * Create a temporary file and return the path
	 *
	 * @param string $postFix
	 * @return string
	 */
	public function getTemporaryFile($postFix = '');

	/**
	 * Create a temporary folder and return the path
	 *
	 * @param string $postFix
	 * @return string
	 */
	public function getTemporaryFolder($postFix = '');

	/**
	 * Remove the temporary files and folders generated during this request
	 */
	public function clean();

	/**
	 * Remove old temporary files and folders that were failed to be cleaned
	 */
	public function cleanOld();
}
