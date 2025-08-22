<?php

namespace OCA\DAV\DAV;

use OCP\Files\Folder;

interface ICacheableDirectory {
	/**
	 * @return Folder[]
	 */
	public function getCacheableDirectories(): array;
}
