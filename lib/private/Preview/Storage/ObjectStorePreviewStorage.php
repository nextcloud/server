<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use Icewind\Streams\CountWrapper;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OCP\Files\ObjectStore\IObjectStore;

class ObjectStorePreviewStorage implements IPreviewStorage {
	private string $objectPrefix = 'urn:oid:preview:';

	/**
	 * @param array{objectPrefix?: string, ...} $parameters
	 */
	public function __construct(
		private readonly IObjectStore $objectStore,
		private readonly array $parameters,
	) {
		if (isset($parameters['objectPrefix'])) {
			$this->objectPrefix = $parameters['objectPrefix'] . 'preview:';
		}
	}

	public function writePreview(Preview $preview, $stream): false|int {
		if (!is_resource($stream)) {
			$fh = fopen('php://temp', 'w+');
			fwrite($fh, $stream);
			rewind($fh);

			$stream = $fh;
		}

		$size = 0;
		$countStream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size): void {
			$size = $writtenSize;
		});

		$this->objectStore->writeObject($this->constructUrn($preview), $countStream);
		return $size;
	}

	public function readPreview(Preview $preview) {
		return $this->objectStore->readObject($this->constructUrn($preview));
	}

	public function deletePreview(Preview $preview) {
		return $this->objectStore->deleteObject($this->constructUrn($preview));
	}

	private function constructUrn(Preview $preview): string {
		return $this->objectPrefix . $preview->getId();
	}

	public function migratePreview(Preview $preview, SimpleFile $file): void {
		if (isset($this->parameters['objectPrefix'])) {
			$objectPrefix = $this->parameters['objectPrefix'];
		} else {
			$objectPrefix = 'urn:oid:';
		}

		$this->objectStore->copyObject($objectPrefix . $file->getId(), $this->constructUrn($preview));
	}
}
