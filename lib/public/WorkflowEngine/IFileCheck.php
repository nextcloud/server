<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

use OCP\Files\Storage\IStorage;

/**
 * Interface IFileCheck
 *
 * @since 18.0.0
 */
interface IFileCheck extends IEntityCheck {
	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @param bool $isDir
	 * @since 18.0.0
	 */
	public function setFileInfo(IStorage $storage, string $path, bool $isDir = false): void;
}
