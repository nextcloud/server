<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\SimpleFS\ISimpleFile;
use Override;

class PreviewFile implements ISimpleFile {
	public function __construct(
		private readonly Preview $preview,
		private readonly IPreviewStorage $storage,
		private readonly PreviewMapper $previewMapper,
		private readonly IMimeTypeLoader $mimeTypeLoader,
	) {
	}

	#[Override]
	public function getName(): string {
		return $this->preview->getName($this->mimeTypeLoader);
	}

	#[Override]
	public function getSize(): int|float {
		return $this->preview->getSize();
	}

	#[Override]
	public function getETag(): string {
		return $this->preview->getEtag();
	}

	#[Override]
	public function getMTime(): int {
		return $this->preview->getMtime();
	}

	#[Override]
	public function getContent(): string {
		$stream = $this->storage->readPreview($this->preview);
		return stream_get_contents($stream);
	}

	#[Override]
	public function putContent($data): void {
	}

	#[Override]
	public function delete(): void {
		$this->storage->deletePreview($this->preview);
		$this->previewMapper->delete($this->preview);
	}

	#[Override]
	public function getMimeType(): string {
		return $this->preview->getMimetypeValue($this->mimeTypeLoader);
	}

	#[Override]
	public function getExtension(): string {
		return $this->preview->getExtension($this->mimeTypeLoader);
	}

	#[Override]
	public function read() {
		return $this->storage->readPreview($this->preview);
	}

	#[Override]
	public function write() {
		return false;
	}
}
