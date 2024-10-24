<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\ShareBackend;

use OCP\Share_Backend_Collection;

class Folder extends File implements Share_Backend_Collection {
	public function getChildren($itemSource) {
		$children = [];
		$parents = [$itemSource];

		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$qb->select('id')
			->from('mimetypes')
			->where(
				$qb->expr()->eq('mimetype', $qb->createNamedParameter('httpd/unix-directory'))
			);
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row = $result->fetchRow()) {
			$mimetype = (int)$row['id'];
		} else {
			$mimetype = -1;
		}
		while (!empty($parents)) {
			$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();

			$parents = array_map(function ($parent) use ($qb) {
				return $qb->createNamedParameter($parent);
			}, $parents);

			$qb->select('`fileid', 'name', '`mimetype')
				->from('filecache')
				->where(
					$qb->expr()->in('parent', $parents)
				);

			$result = $qb->execute();

			$parents = [];
			while ($file = $result->fetch()) {
				$children[] = ['source' => $file['fileid'], 'file_path' => $file['name']];
				// If a child folder is found look inside it
				if ((int)$file['mimetype'] === $mimetype) {
					$parents[] = $file['fileid'];
				}
			}
			$result->closeCursor();
		}
		return $children;
	}
}
