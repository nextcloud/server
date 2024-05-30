<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Storage;

/**
 * Marks a storage as providing reliable etags according to expected behavior of etags within nextcloud:
 *
 * - Etag are stable as long as no changes are made to the files
 * - Changes inside a folder cause etag changes of the parent folders
 *
 * @since 24.0.0
 */
interface IReliableEtagStorage extends IStorage {
}
