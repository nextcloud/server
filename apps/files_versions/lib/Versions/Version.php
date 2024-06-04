<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
