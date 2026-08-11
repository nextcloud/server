<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Provides request-scoped temporary files and folders.
 *
 * Temporary resources are created in the configured temporary directory and
 * tracked for removal by {@see clean()} during the current request. Resources
 * left behind by earlier requests can be removed later with {@see cleanOld()}.
 *
 * The returned paths are local filesystem paths, not Nextcloud VFS paths.
 *
 * @since 8.0.0
 */
interface ITempManager {
	/**
	 * Create an empty temporary file and return its local filesystem path.
	 *
	 * The file is created in the configured temporary directory and registered
	 * for cleanup by {@see clean()}. The returned path is a local filesystem
	 * path, not a Nextcloud VFS path.
	 *
	 * @param string $postFix Optional suffix appended to the temporary filename,
	 *                        such as `tar.gz`
	 * @return string|false The local filesystem path, or false if the file could
	 *                      not be created
	 *
	 * @since 8.0.0
	 */
	public function getTemporaryFile(string $postFix = ''): string|false;

	/**
	 * Create an empty temporary folder and return its local filesystem path.
	 *
	 * The directory is created with restrictive permissions and is registered
	 * for cleanup by {@see clean()}. The returned path is a local filesystem
	 * path, not a Nextcloud VFS path.
	 *
	 * @param string $postFix Optional directory-name suffix
	 * @return string|false The local filesystem path, or false if the directory
	 *                      could not be created
	 *
	 * @since 8.0.0
	 */
	public function getTemporaryFolder(string $postFix = ''): string|false;

	/**
	 * Remove temporary files and folders created during this request.
	 *
	 * Cleanup failures may be logged. This method does not report whether all
	 * registered paths were removed successfully.
	 *
	 * @return void
	 *
	 * @since 8.0.0
	 */
	public function clean(): void;

	/**
	 * Remove temporary files and folders left over from previous requests.
	 *
	 * Cleanup failures may be logged. Only entries created by this temporary
	 * manager whose modification time is more than one hour old are considered.
	 *
	 * Use this method with care: resources left over from previous requests are
	 * not scoped to the current request or app and may belong to another process.
	 *
	 * @return void
	 *
	 * @since 8.0.0
	 */
	public function cleanOld(): void;

	/**
	 * Get the effective temporary directory used by this manager.
	 *
	 * Use this method with care. Unlike {@see getTemporaryFile()} and
	 * {@see getTemporaryFolder()}, files and folders created directly in the
	 * returned directory are not registered with the temporary manager and do not
	 * automatically follow its temporary-file naming convention. Callers are
	 * responsible for cleanup and must take care to avoid interfering with
	 * unrelated files.
	 *
	 * @return string Writable local filesystem directory
	 * @throws \UnexpectedValueException If no usable temporary directory can be
	 *                                   detected
	 *
	 * @since 8.2.0
	 */
	public function getTempBaseDir(): string;
}
