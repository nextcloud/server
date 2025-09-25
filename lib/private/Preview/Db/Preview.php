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
 * Preview entity mapped to the oc_previews and oc_preview_locations table.
 *
 * @method int getFileId() Get the file id of the original file.
 * @method void setFileId(int $fileId)
 * @method int getStorageId() Get the storage id of the original file.
 * @method void setStorageId(int $fileId)
 * @method int getOldFileId() Get the old location in the file-cache table, for legacy compatibility.
 * @method void setOldFileId(int $oldFileId)
 * @method int getLocationId() Get the location id in the preview_locations table. Only set when using an object store as primary storage.
 * @method void setLocationId(int $locationId)
 * @method string getBucketName() Get the bucket name where the preview is stored. This is stored in the preview_locations table.
 * @method string getObjectStoreName() Get the object store name where the preview is stored. This is stored in the preview_locations table.
 * @method int getWidth() Get the width of the preview.
 * @method void setWidth(int $width)
 * @method int getHeight() Get the height of the preview.
 * @method void setHeight(int $height)
 * @method bool isCropped() Get whether the preview is cropped or not.
 * @method void setCropped(bool $cropped)
 * @method void setMimetype(int $mimetype) Set the mimetype of the preview.
 * @method int getMimetype() Get the mimetype of the preview.
 * @method int getMtime() Get the modification time of the preview.
 * @method void setMtime(int $mtime)
 * @method int getSize() Get the size of the preview.
 * @method void setSize(int $size)
 * @method bool isMax() Get whether the preview is the biggest one which is then used to generate the smaller previews.
 * @method void setMax(bool $max)
 * @method string getEtag() Get the etag of the preview.
 * @method void setEtag(string $etag)
 * @method int|null getVersion() Get the version for files_versions_s3
 * @method void setVersion(?int $version)
 * @method bool|null getIs() Get the version for files_versions_s3
 * @method bool isEncrypted() Get whether the preview is encrypted. At the moment every preview is unencrypted.
 * @method void setEncrypted(bool $encrypted)
 *
 * @see PreviewMapper
 */
class Preview extends Entity {
	protected ?int $fileId = null;
	protected ?int $oldFileId = null;
	protected ?int $storageId = null;
	protected ?int $locationId = null;
	protected ?string $bucketName = null;
	protected ?string $objectStoreName = null;
	protected ?int $width = null;
	protected ?int $height = null;
	protected ?int $mimetype = null;
	protected ?int $mtime = null;
	protected ?int $size = null;
	protected ?bool $max = null;
	protected ?bool $cropped = null;
	protected ?string $etag = null;
	protected ?int $version = null;
	protected ?bool $encrypted = null;

	public function __construct() {
		$this->addType('fileId', Types::BIGINT);
		$this->addType('storageId', Types::BIGINT);
		$this->addType('oldFileId', Types::BIGINT);
		$this->addType('locationId', Types::BIGINT);
		$this->addType('width', Types::INTEGER);
		$this->addType('height', Types::INTEGER);
		$this->addType('mimetype', Types::INTEGER);
		$this->addType('mtime', Types::INTEGER);
		$this->addType('size', Types::INTEGER);
		$this->addType('max', Types::BOOLEAN);
		$this->addType('cropped', Types::BOOLEAN);
		$this->addType('encrypted', Types::BOOLEAN);
		$this->addType('etag', Types::STRING);
		$this->addType('version', Types::BIGINT);
	}

	public static function fromPath(string $path): Preview {
		$preview = new self();
		$preview->setFileId((int)basename(dirname($path)));

		$fileName = pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);

		[0 => $baseName, 1 => $extension] = explode('.', $fileName);
		$preview->setMimetype(match ($extension) {
			'jpg' | 'jpeg' => IPreview::MIMETYPE_JPEG,
			'png' => IPreview::MIMETYPE_PNG,
			'gif' => IPreview::MIMETYPE_GIF,
			'webp' => IPreview::MIMETYPE_WEBP,
			default => IPreview::MIMETYPE_JPEG,
		});
		$nameSplit = explode('-', $baseName);

		$offset = 0;
		$preview->setVersion(null);
		if (count($nameSplit) === 4 || (count($nameSplit) === 3 && is_numeric($nameSplit[2]))) {
			$offset = 1;
			$preview->setVersion((int)$nameSplit[0]);
		}

		$preview->setWidth((int)$nameSplit[$offset + 0]);
		$preview->setHeight((int)$nameSplit[$offset + 1]);

		$preview->setCropped(false);
		$preview->setMax(false);
		if (isset($nameSplit[$offset + 2])) {
			$preview->setCropped($nameSplit[$offset + 2] === 'crop');
			$preview->setMax($nameSplit[$offset + 2] === 'max');
		}
		return $preview;
	}

	public function getName(): string {
		$path = ($this->getVersion() > -1 ? $this->getVersion() . '-' : '') . $this->getWidth() . '-' . $this->getHeight();
		if ($this->isCropped()) {
			$path .= '-crop';
		}
		if ($this->isMax()) {
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
			IPreview::MIMETYPE_JPEG => 'jpg',
			IPreview::MIMETYPE_PNG => 'png',
			IPreview::MIMETYPE_WEBP => 'webp',
			IPreview::MIMETYPE_GIF => 'gif',
		};
	}

	public function setBucketName(string $bucketName): void {
		$this->bucketName = $bucketName;
	}

	public function setObjectStoreName(string $objectStoreName): void {
		$this->objectStoreName = $objectStoreName;
	}
}
