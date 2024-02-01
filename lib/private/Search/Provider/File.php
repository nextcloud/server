<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andrew Brown <andrew@casabrown.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Search\Provider;

use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\IUserSession;
use OCP\Search\PagedProvider;

/**
 * Provide search results from the 'files' app
 * @deprecated 20.0.0
 */
class File extends PagedProvider {
	/**
	 * Search for files and folders matching the given query
	 *
	 * @param string $query
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return \OCP\Search\Result[]
	 * @deprecated 20.0.0
	 */
	public function search($query, int $limit = null, int $offset = null) {
		/** @var IRootFolder $rootFolder */
		$rootFolder = \OCP\Server::get(IRootFolder::class);
		/** @var IUserSession $userSession */
		$userSession = \OCP\Server::get(IUserSession::class);
		$user = $userSession->getUser();
		if (!$user) {
			return [];
		}
		$userFolder = $rootFolder->getUserFolder($user->getUID());
		$fileQuery = new SearchQuery(
			new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%' . $query . '%'),
			(int)$limit,
			(int)$offset,
			[
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
			],
			$user
		);
		$files = $userFolder->search($fileQuery);
		$results = [];
		// edit results
		foreach ($files as $fileData) {
			// create audio result
			if ($fileData->getMimePart() === 'audio') {
				$result = new \OC\Search\Result\Audio($fileData);
			}
			// create image result
			elseif ($fileData->getMimePart() === 'image') {
				$result = new \OC\Search\Result\Image($fileData);
			}
			// create folder result
			elseif ($fileData->getMimetype() === FileInfo::MIMETYPE_FOLDER) {
				$result = new \OC\Search\Result\Folder($fileData);
			}
			// or create file result
			else {
				$result = new \OC\Search\Result\File($fileData);
			}
			// add to results
			$results[] = $result;
		}
		// return
		return $results;
	}

	public function searchPaged($query, $page, $size) {
		if ($size === 0) {
			return $this->search($query);
		} else {
			return $this->search($query, $size, ($page - 1) * $size);
		}
	}
}
