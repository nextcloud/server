<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
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

	public function getLabel(): string {
		return $this->metadata['label'] ?? '';
	}

	public function setLabel(string $label): void {
		$this->metadata['label'] = $label;
		$this->markFieldUpdated('metadata');
	}
}
