<?php

namespace OC\Preview\Storage;

use OC\Preview\Db\Preview;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

interface IPreviewStorage {
	/**
	 * @param resource|string $stream
	 * @throws NotPermittedException
	 */
	public function writePreview(Preview $preview, $stream): false|int;

	/**
	 * @param Preview $preview
	 * @return resource|false
	 */
	public function readPreview(Preview $preview);

	public function deletePreview(Preview $preview);

	public function migratePreview(Preview $preview, ISimpleFile $simpleFile);
}
