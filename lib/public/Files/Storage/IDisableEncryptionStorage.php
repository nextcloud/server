<?php

declare (strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Storage;

/**
 * Marks that a storage does not support server side encryption
 *
 * @since 16.0.0
 */
interface IDisableEncryptionStorage extends IStorage {
}
