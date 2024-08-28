<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method int getFileId()
 * @method void setFileId(int $fileId)
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 * @method int|float getSize()
 * @method void setSize(int|float $size)
 * @method int getMimetype()
 * @method void setMimetype(int $mimetype)
 * @method array|null getMetadata()
 * @method void setMetadata(array $metadata)
 */
class VersionEntity extends Entity implements JsonSerializable {
	protected ?int $fileId = null;
	protected ?int $timestamp = null;
	protected ?int $size = null;
	protected ?int $mimetype = null;
	protected ?array $metadata = null;

	public function __construct() {
		$this->addType('id', Types::INTEGER);
		$this->addType('file_id', Types::INTEGER);
		$this->addType('timestamp', Types::INTEGER);
		$this->addType('size', Types::INTEGER);
		$this->addType('mimetype', Types::INTEGER);
		$this->addType('metadata', Types::JSON);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'file_id' => $this->fileId,
			'timestamp' => $this->timestamp,
			'size' => $this->size,
			'mimetype' => $this->mimetype,
			'metadata' => $this->metadata,
		];
	}

	/**
	 * @abstract given a key, return the value associated with the key in the metadata column
	 * if nothing is found, we return an empty string
	 * @param string $key key associated with the value
	 */
	public function getMetadataValue(string $key): ?string {
		return $this->metadata[$key] ?? null;
	}

	/**
	 * @abstract sets a key value pair in the metadata column
	 * @param string $key key associated with the value
	 * @param string $value value associated with the key
	 */
	public function setMetadataValue(string $key, string $value): void {
		$this->metadata[$key] = $value;
		$this->markFieldUpdated('metadata');
	}
}
