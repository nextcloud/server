<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\ShareBackend;

class Folder extends File implements \OCP\Share_Backend_Collection {
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
			$mimetype = (int) $row['id'];
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
				if ((int) $file['mimetype'] === $mimetype) {
					$parents[] = $file['fileid'];
				}
			}
			$result->closeCursor();
		}
		return $children;
	}
}
