<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC;

use OCP\IImage;
use OCP\IStreamImage;

/**
 * Only useful when dealing with transferring streamed previews from an external
 * service to an object store.
 *
 * Only width/height/resource and mimeType are implemented and will give you a
 * valid result.
 */
class StreamImage implements IStreamImage {
	/** @param resource $stream */
	public function __construct(
		private $stream,
		private string $mimeType,
		private int $width,
		private int $height,
	) {
	}

	/** @inheritDoc */
	public function valid(): bool {
		return is_resource($this->stream);
	}

	/** @inheritDoc */
	public function mimeType(): ?string {
		return $this->mimeType;
	}

	/** @inheritDoc */
	public function width(): int {
		return $this->width;
	}

	/** @inheritDoc */
	public function height(): int {
		return $this->height;
	}

	public function widthTopLeft(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	public function heightTopLeft(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	public function show(?string $mimeType = null): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function save(?string $filePath = null, ?string $mimeType = null): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function resource() {
		return $this->stream;
	}

	public function dataMimeType(): ?string {
		return $this->mimeType;
	}

	public function data(): ?string {
		return '';
	}

	public function getOrientation(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	public function fixOrientation(): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function resize(int $maxSize): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function preciseResize(int $width, int $height): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function centerCrop(int $size = 0): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function crop(int $x, int $y, int $w, int $h): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function fitIn(int $maxWidth, int $maxHeight): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function scaleDownToFit(int $maxWidth, int $maxHeight): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function copy(): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	public function preciseResizeCopy(int $width, int $height): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	public function resizeCopy(int $maxSize): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	public function loadFromData(string $str): \GdImage|false {
		throw new \BadMethodCallException('Not implemented');
	}

	public function readExif(string $data): void {
		throw new \BadMethodCallException('Not implemented');
	}
}
