<?php
/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
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

namespace OC;

use OCP\IStreamImage;
use OCP\IImage;

/**
 * Only useful when dealing with transferring streamed previews from an external
 * service to an object store.
 *
 * Only width/height/resource and mimeType are implemented and will give you a
 * valid result.
 */
class StreamImage implements IStreamImage {
	/** @var resource The internal stream */
	private $stream;

	/** @var string */
	private $mimeType;

	/** @var int */
	private $width;

	/** @var int */
	private $height;

	/** @param resource $stream */
	public function __construct($stream, string $mimeType, int $width, int $height) {
		$this->stream = $stream;
		$this->mimeType = $mimeType;
		$this->width = $width;
		$this->height = $height;
	}

	/** @inheritDoc */
	public function valid() {
		return is_resource($this->stream);
	}

	/** @inheritDoc */
	public function mimeType() {
		return $this->mimeType;
	}

	/** @inheritDoc */
	public function width() {
		return $this->width;
	}

	/** @inheritDoc */
	public function height() {
		return $this->height;
	}

	public function widthTopLeft() {
		throw new \BadMethodCallException('Not implemented');
	}

	public function heightTopLeft() {
		throw new \BadMethodCallException('Not implemented');
	}

	public function show($mimeType = null) {
		throw new \BadMethodCallException('Not implemented');
	}

	public function save($filePath = null, $mimeType = null) {
		throw new \BadMethodCallException('Not implemented');
	}

	public function resource() {
		return $this->stream;
	}

	public function dataMimeType() {
		return $this->mimeType;
	}

	public function data() {
		return '';
	}

	public function getOrientation() {
		throw new \BadMethodCallException('Not implemented');
	}

	public function fixOrientation() {
		throw new \BadMethodCallException('Not implemented');
	}

	public function resize($maxSize) {
		throw new \BadMethodCallException('Not implemented');
	}

	public function preciseResize(int $width, int $height): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function centerCrop($size = 0) {
		throw new \BadMethodCallException('Not implemented');
	}

	public function crop(int $x, int $y, int $w, int $h): bool {
		throw new \BadMethodCallException('Not implemented');
	}

	public function fitIn($maxWidth, $maxHeight) {
		throw new \BadMethodCallException('Not implemented');
	}

	public function scaleDownToFit($maxWidth, $maxHeight) {
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
}
