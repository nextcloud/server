<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Preview;

/**
 * Marks files that should keep multiple preview "versions" for the same file id
 *
 * Examples of this are files where the storage backend provides versioning, for those
 * files, we dont have fileids for the different versions but still need to be able to generate
 * previews for all versions
 *
 * @since 17.0.0
 */
interface IVersionedPreviewFile {
	/**
	 * @return string
	 * @since 17.0.0
	 */
	public function getPreviewVersion(): string;
}
