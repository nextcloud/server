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

	#[\Override]
	public function getBackend(): IVersionBackend {
		return $this->backend;
	}

	#[\Override]
	public function getSourceFile(): FileInfo {
		return $this->sourceFileInfo;
	}

	#[\Override]
	public function getRevisionId() {
		return $this->revisionId;
	}

	#[\Override]
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	#[\Override]
	public function getSize(): int|float {
		return $this->size;
	}

	#[\Override]
	public function getSourceFileName(): string {
		return $this->name;
	}

	#[\Override]
	public function getMimeType(): string {
		return $this->mimetype;
	}

	#[\Override]
	public function getVersionPath(): string {
		return $this->path;
	}

	#[\Override]
	public function getUser(): IUser {
		return $this->user;
	}

	#[\Override]
	public function getMetadata(): array {
		return $this->metadata;
	}

	#[\Override]
	public function getMetadataValue(string $key): ?string {
		return $this->metadata[$key] ?? null;
	}
}
