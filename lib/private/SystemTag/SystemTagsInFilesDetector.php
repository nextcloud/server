<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		int $offset = 0,
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
