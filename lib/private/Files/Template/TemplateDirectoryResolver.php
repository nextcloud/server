<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Template;

use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/** Find contextual templates without leaving the destination's mount. */
class TemplateDirectoryResolver {
	/** @return list<Folder> */
	public function resolve(Folder $userFolder, string $targetPath): array {
		$folders = [];
		try {
			$folder = $userFolder->get($targetPath);
			if (!$folder instanceof Folder || !$folder->isReadable()) {
				return [];
			}
			$mount = $folder->getMountPoint()->getMountPoint();
			while ($folder->isReadable()
				&& $userFolder->getRelativePath($folder->getPath()) !== null
				&& $folder->getMountPoint()->getMountPoint() === $mount) {
				try {
					$templates = $folder->get('.Templates');
					if ($templates instanceof Folder && $templates->isReadable()) {
						$folders[] = $templates;
					}
				} catch (NotFoundException|NotPermittedException $e) {
				}
				if ($folder->getPath() === $userFolder->getPath()) {
					break;
				}
				$folder = $folder->getParent();
			}
		} catch (NotFoundException|NotPermittedException|InvalidPathException $e) {
		}
		return $folders;
	}
}
