<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Interface ITempManager
 *
 * @since 8.0.0
 */
interface ITempManager {
	/**
	 * Create a temporary file and return the path
	 *
	 * @param string $postFix
	 * @return string
	 * @since 8.0.0
	 */
	public function getTemporaryFile($postFix = '');

	/**
	 * Create a temporary folder and return the path
	 *
	 * @param string $postFix
	 * @return string
	 * @since 8.0.0
	 */
	public function getTemporaryFolder($postFix = '');

	/**
	 * Remove the temporary files and folders generated during this request
	 * @since 8.0.0
	 */
	public function clean();

	/**
	 * Remove old temporary files and folders that were failed to be cleaned
	 * @since 8.0.0
	 */
	public function cleanOld();

	/**
	 * Get the temporary base directory
	 *
	 * @return string
	 * @since 8.2.0
	 */
	public function getTempBaseDir();
}
