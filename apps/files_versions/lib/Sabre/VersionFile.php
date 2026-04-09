<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Sabre;

use OCA\Files_Versions\Versions\IDeletableVersionBackend;
use OCA\Files_Versions\Versions\IMetadataVersion;
use OCA\Files_Versions\Versions\IMetadataVersionBackend;
use OCA\Files_Versions\Versions\INameableVersion;
use OCA\Files_Versions\Versions\INameableVersionBackend;
use OCA\Files_Versions\Versions\IVersion;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\Files\NotFoundException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IFile;

class VersionFile implements IFile {
	public function __construct(
		private IVersion $version,
		private IVersionManager $versionManager,
	) {
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		try {
			return $this->versionManager->read($this->version);
		} catch (NotFoundException $e) {
			throw new NotFound();
		}
	}

	public function getContentType(): string {
		return $this->version->getMimeType();
	}

	public function getETag(): string {
		return (string)$this->version->getRevisionId();
	}

	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	public function getSize(): int|float {
		return $this->version->getSize();
	}

	public function delete() {
		if ($this->versionManager instanceof IDeletableVersionBackend) {
			$this->versionManager->deleteVersion($this->version);
		} else {
			throw new Forbidden();
		}
	}

	public function getName(): string {
		return (string)$this->version->getRevisionId();
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function setMetadataValue(string $key, string $value): bool {
		$backend = $this->version->getBackend();

		if ($backend instanceof IMetadataVersionBackend) {
			$backend->setMetadataValue($this->version->getSourceFile(), $this->version->getTimestamp(), $key, $value);
			return true;
		} elseif ($key === 'label' && $backend instanceof INameableVersionBackend) {
			$backend->setVersionLabel($this->version, $value);
			return true;
		} else {
			return false;
		}
	}

	public function getMetadataValue(string $key): ?string {
		if ($this->version instanceof IMetadataVersion) {
			return $this->version->getMetadataValue($key);
		} elseif ($key === 'label' && $this->version instanceof INameableVersion) {
			return $this->version->getLabel();
		}
		return null;
	}

	public function getLastModified(): int {
		return $this->version->getTimestamp();
	}

	public function rollBack() {
		$this->versionManager->rollback($this->version);
	}

	public function getVersion(): IVersion {
		return $this->version;
	}
}
