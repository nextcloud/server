<?php

declare(strict_types=1);

/*
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\Files\Storage;

/**
 * @since 29.0.0
 */
interface IMtimePreserving extends IStorage {

	/**
	 * see https://www.php.net/manual/en/function.copy.php
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 * @since 9.0.0
	 * @deprecated 29.0.0 see copyWithMtime
	 */
	public function copy($source, $target, bool $preserveMtime = false): bool;

	/**
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @since 29.0.0
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath, bool $preserveMtime = false): bool;

	/**
	 * see https://www.php.net/manual/en/function.copy.php
	 *
	 * @since 29.0.0
	 */
	// public function copyWithMtime(string $source, string $target, bool $preserveMtime = false): bool;

	/**
	 * @since 29.0.0
	 */
	// public function copyFromStorageWithMtime(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath, bool $preserveMtime = false): bool;
}
