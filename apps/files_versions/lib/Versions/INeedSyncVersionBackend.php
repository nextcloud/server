<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Louis Chmn <louis@chmn.me>
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
namespace OCA\Files_Versions\Versions;

use OCP\Files\File;

/**
 * @since 28.0.0
 */
interface INeedSyncVersionBackend {
	public function createVersionEntity(File $file): void;
	public function updateVersionEntity(File $sourceFile, int $revision, array $properties): void;
	public function deleteVersionsEntity(File $file): void;
}
