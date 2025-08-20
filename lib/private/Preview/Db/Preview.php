<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;
use OCP\IPreview;

/**
 * @method \int getFileId()
 * @method void setFileId(int $fileId)
 * @method \int getWidth()
 * @method void setWidth(int $width)
 * @method \int getHeight()
 * @method void setHeight(int $height)
 * @method \int getMode()
 * @method void setMode(int $mode)
 * @method \bool getCrop()
 * @method void setCrop(bool $crop)
 * @method void setMimetype(int $mimetype)
 * @method IPreview::MIMETYPE_* getMimetype()
 * @method \int getMtime()
 * @method void setMtime(int $mtime)
 * @method \int getSize()
 * @method void setSize(int $size)
 * @method \bool getIsMax()
 * @method void setIsMax(bool $max)
 * @method \string getEtag()
 * @method void setEtag(string $etag)
 * @method ?\int getVersion()
 * @method void setVersion(?int $version)
 */
class Preview extends Entity {
	protected ?int $fileId = null;

	protected ?int $width = null;

	protected ?int $height = null;

	protected ?int $mimetype = null;

	protected ?int $mtime = null;

	protected ?int $size = null;

	protected ?bool $isMax = null;

	protected ?bool $crop = null;

	protected ?string $etag = null;
	protected ?int $version = null;

	public function __construct() {
		$this->addType('fileId', Types::INTEGER);
		$this->addType('width', Types::INTEGER);
		$this->addType('height', Types::INTEGER);
		$this->addType('mimetype', Types::INTEGER);
		$this->addType('mtime', Types::INTEGER);
		$this->addType('size', Types::INTEGER);
		$this->addType('isMax', Types::BOOLEAN);
		$this->addType('crop', Types::BOOLEAN);
		$this->addType('etag', Types::STRING);
		$this->addType('version', Types::INTEGER);
	}

	public function getName(): string {
		$path = ($this->getVersion() ? $this->getVersion() . '-' : '') . $this->getWidth() . '-' . $this->getHeight();
		if ($this->getCrop()) {
			$path .= '-crop';
		}
		if ($this->getIsMax()) {
			$path .= '-max';
		}

		$ext = $this->getExtension();
		$path .= '.' . $ext;
		return $path;
	}

	public function getMimetypeValue(): string {
		return match ($this->mimetype) {
			IPreview::MIMETYPE_JPEG => 'image/jpeg',
			IPreview::MIMETYPE_PNG => 'image/png',
			IPreview::MIMETYPE_WEBP => 'image/webp',
			IPreview::MIMETYPE_GIF => 'image/gif',
		};
	}

	public function getExtension(): string {
		return match ($this->mimetype) {
			IPreview::MIMETYPE_JPEG => 'jpeg',
			IPreview::MIMETYPE_PNG => 'png',
			IPreview::MIMETYPE_WEBP => 'webp',
			IPreview::MIMETYPE_GIF => 'gif',
		};
	}
}
