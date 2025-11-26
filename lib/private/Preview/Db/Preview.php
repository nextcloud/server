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
use OCP\Files\IMimeTypeDetector;

/**
 * Preview entity mapped to the oc_previews and oc_preview_locations table.
 *
 * @method string getId()
 * @method void setId(string $id)
 * @method int getFileId() Get the file id of the original file.
 * @method void setFileId(int $fileId)
 * @method int getStorageId() Get the storage id of the original file.
 * @method void setStorageId(int $fileId)
 * @method int getOldFileId() Get the old location in the file-cache table, for legacy compatibility.
 * @method void setOldFileId(int $oldFileId)
 * @method string getLocationId() Get the location id in the preview_locations table. Only set when using an object store as primary storage.
 * @method void setLocationId(string $locationId)
 * @method string|null getBucketName() Get the bucket name where the preview is stored. This is stored in the preview_locations table.
 * @method string|null getObjectStoreName() Get the object store name where the preview is stored. This is stored in the preview_locations table.
 * @method int getWidth() Get the width of the preview.
 * @method void setWidth(int $width)
 * @method int getHeight() Get the height of the preview.
 * @method void setHeight(int $height)
 * @method bool isCropped() Get whether the preview is cropped or not.
 * @method void setCropped(bool $cropped)
 * @method void setMimetypeId(int $mimetype) Set the mimetype of the preview.
 * @method int getMimetypeId() Get the mimetype of the preview.
 * @method void setSourceMimetypeId(int $sourceMimetype) Set the mimetype of the source file.
 * @method int getSourceMimetypeId() Get the mimetype of the source file.
 * @method int getMtime() Get the modification time of the preview.
 * @method void setMtime(int $mtime)
 * @method int getSize() Get the size of the preview.
 * @method void setSize(int $size)
 * @method bool isMax() Get whether the preview is the biggest one which is then used to generate the smaller previews.
 * @method void setMax(bool $max)
 * @method string getEtag() Get the etag of the preview.
 * @method void setEtag(string $etag)
 * @method string|null getVersion() Get the version for files_versions_s3
 * @method void setVersionId(string $versionId)
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
	protected ?string $locationId = null;
	protected ?string $bucketName = null;
	protected ?string $objectStoreName = null;
	protected ?int $width = null;
	protected ?int $height = null;
	protected ?int $mimetypeId = null;
	protected ?int $sourceMimetypeId = null;
	protected string $mimetype = 'application/octet-stream';
	protected string $sourceMimetype = 'application/octet-stream';
	protected ?int $mtime = null;
	protected ?int $size = null;
	protected ?bool $max = null;
	protected ?bool $cropped = null;
	protected ?string $etag = null;
	protected ?string $version = null;
	protected ?string $versionId = null;
	protected ?bool $encrypted = null;

	public function __construct() {
		$this->addType('id', Types::STRING);
		$this->addType('fileId', Types::BIGINT);
		$this->addType('storageId', Types::BIGINT);
		$this->addType('oldFileId', Types::BIGINT);
		$this->addType('locationId', Types::STRING);
		$this->addType('width', Types::INTEGER);
		$this->addType('height', Types::INTEGER);
		$this->addType('mimetypeId', Types::INTEGER);
		$this->addType('sourceMimetypeId', Types::INTEGER);
		$this->addType('mtime', Types::INTEGER);
		$this->addType('size', Types::INTEGER);
		$this->addType('max', Types::BOOLEAN);
		$this->addType('cropped', Types::BOOLEAN);
		$this->addType('encrypted', Types::BOOLEAN);
		$this->addType('etag', Types::STRING);
		$this->addType('versionId', Types::STRING);
	}

	public static function fromPath(string $path, IMimeTypeDetector $mimeTypeDetector): Preview|false {
		$preview = new self();
		$preview->setFileId((int)basename(dirname($path)));

		$fileName = pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
		$ok = preg_match('/(([A-Za-z0-9\+\/]+)-)?([0-9]+)-([0-9]+)(-(max))?(-(crop))?\.([a-z]{3,4})/', $fileName, $matches);

		if ($ok !== 1) {
			return false;
		}

		[
			2 => $version,
			3 => $width,
			4 => $height,
			6 => $max,
			8 => $crop,
		] = $matches;

		$preview->setMimeType($mimeTypeDetector->detectPath($fileName));

		$preview->setWidth((int)$width);
		$preview->setHeight((int)$height);
		$preview->setCropped($crop === 'crop');
		$preview->setMax($max === 'max');

		if (!empty($version)) {
			$preview->setVersion($version);
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

	public function getExtension(): string {
		return match ($this->getMimeType()) {
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/webp' => 'webp',
			default => 'png',
		};
	}

	public function setBucketName(string $bucketName): void {
		$this->bucketName = $bucketName;
	}

	public function setObjectStoreName(string $objectStoreName): void {
		$this->objectStoreName = $objectStoreName;
	}

	public function setVersion(?string $version): void {
		$this->version = $version;
	}

	public function getMimeType(): string {
		return $this->mimetype;
	}

	public function setMimeType(string $mimeType): void {
		$this->mimetype = $mimeType;
	}

	public function getSourceMimeType(): string {
		return $this->sourceMimetype;
	}

	public function setSourceMimeType(string $mimeType): void {
		$this->sourceMimetype = $mimeType;
	}
}
