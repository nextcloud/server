<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OC\SystemTag;

use OC\Files\Cache\QuerySearchHelper;
use OC\Files\Node\Root;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OCP\Files\Folder;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;

class SystemTagsInFilesDetector {
	public function __construct(
		protected QuerySearchHelper $searchHelper,
	) {
	}

	public function detectAssignedSystemTagsIn(
		Folder $folder,
		string $filteredMediaType = '',
		int $limit = 0,
		int $offset = 0
	): array {
		$operator = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'systemtag', '%');
		// Currently query has to have exactly one search condition. If no media type is provided,
		// we fall back to the presence of a system tag.
		if ($filteredMediaType !== '') {
			$mimeOperator = new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', $filteredMediaType . '/%');
			$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [$operator, $mimeOperator]);
		}

		$query = new SearchQuery($operator, $limit, $offset, []);
		[$caches, ] = $this->searchHelper->getCachesAndMountPointsForSearch(
			$this->getRootFolder($folder),
			$folder->getPath(),
		);
		return $this->searchHelper->findUsedTagsInCaches($query, $caches);
	}

	protected function getRootFolder(?Folder $folder): Root {
		if ($folder instanceof Root) {
			return $folder;
		} elseif ($folder === null) {
			throw new \LogicException('Could not climb up to root folder');
		}
		return $this->getRootFolder($folder->getParent());
	}
}
