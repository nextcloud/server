<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC;

use OCP\IImage;
use OCP\IStreamImage;
use Override;

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
		private ?string $mimeType,
		private int $width,
		private int $height,
	) {
	}

	#[Override]
	public function valid(): bool {
		return is_resource($this->stream);
	}

	#[Override]
	public function mimeType(): ?string {
		return $this->mimeType;
	}

	#[Override]
	public function width(): int {
		return $this->width;
	}

	#[Override]
	public function height(): int {
		return $this->height;
	}

	#[Override]
	public function widthTopLeft(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function heightTopLeft(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function show(?string $mimeType = null): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function save(?string $filePath = null, ?string $mimeType = null): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function resource() {
		return $this->stream;
	}

	#[Override]
	public function dataMimeType(): ?string {
		return $this->mimeType;
	}

	#[Override]
	public function data(): ?string {
		return '';
	}

	#[Override]
	public function getOrientation(): int {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function fixOrientation(): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function resize(int $maxSize): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function preciseResize(int $width, int $height): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function centerCrop(int $size = 0): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function crop(int $x, int $y, int $w, int $h): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function fitIn(int $maxWidth, int $maxHeight): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function scaleDownToFit(int $maxWidth, int $maxHeight): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function copy(): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function preciseResizeCopy(int $width, int $height): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function resizeCopy(int $maxSize): IImage {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function loadFromData(string $str): \GdImage|false {
		throw new \BadMethodCallException('Not implemented');
	}

	#[Override]
	public function readExif(string $data): void {
		throw new \BadMethodCallException('Not implemented');
	}
}
