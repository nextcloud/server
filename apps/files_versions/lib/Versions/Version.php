<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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

use OCP\Files\FileInfo;
use OCP\IUser;

class Version implements IVersion, IMetadataVersion {
	public function __construct(
		private int $timestamp,
		private int|string $revisionId,
		private string $name,
		private int|float $size,
		private string $mimetype,
		private string $path,
		private FileInfo $sourceFileInfo,
		private IVersionBackend $backend,
		private IUser $user,
		private array $metadata = [],
	) {
	}

	public function getBackend(): IVersionBackend {
		return $this->backend;
	}

	public function getSourceFile(): FileInfo {
		return $this->sourceFileInfo;
	}

	public function getRevisionId() {
		return $this->revisionId;
	}

	public function getTimestamp(): int {
		return $this->timestamp;
	}

	public function getSize(): int|float {
		return $this->size;
	}

	public function getSourceFileName(): string {
		return $this->name;
	}

	public function getMimeType(): string {
		return $this->mimetype;
	}

	public function getVersionPath(): string {
		return $this->path;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getMetadata(): array {
		return $this->metadata;
	}

	public function getMetadataValue(string $key): ?string {
		return $this->metadata[$key] ?? null;
	}
}
