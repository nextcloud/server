<?php

namespace OC\Preview\Storage;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\Files\SimpleFS\ISimpleFile;

class PreviewFile implements ISimpleFile {
	public function __construct(private Preview $preview, private IPreviewStorage $storage, private PreviewMapper $previewMapper) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->preview->getName();
	}

	/**
	 * @inheritDoc
	 */
	public function getSize(): int|float {
		return $this->preview->getSize();
	}

	/**
	 * @inheritDoc
	 */
	public function getETag(): string {
		return $this->preview->getEtag();
	}

	/**
	 * @inheritDoc
	 */
	public function getMTime(): int {
		return $this->preview->getMtime();
	}

	/**
	 * @inheritDoc
	 */
	public function getContent(): string {
		$stream = $this->storage->readPreview($this->preview);
		return stream_get_contents($stream);
	}

	/**
	 * @inheritDoc
	 */
	public function putContent($data): void {
	}

	/**
	 * @inheritDoc
	 */
	public function delete(): void {
		$this->storage->deletePreview($this->preview);
		$this->previewMapper->delete($this->preview);
	}

	/**
	 * @inheritDoc
	 */
	public function getMimeType(): string {
		return $this->preview->getMimetypeValue();
	}

	/**
	 * @inheritDoc
	 */
	public function getExtension(): string {
		return $this->preview->getExtension();
	}

	/**
	 * @inheritDoc
	 */
	public function read() {
		return $this->storage->readPreview($this->preview);
	}

	/**
	 * @inheritDoc
	 */
	public function write() {
		return false;
	}
}
