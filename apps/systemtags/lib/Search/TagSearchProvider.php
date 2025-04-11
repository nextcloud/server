<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Search;

use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class TagSearchProvider implements IProvider {

	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IMimeTypeDetector $mimeTypeDetector,
		private IRootFolder $rootFolder,
		private ISystemTagObjectMapper $objectMapper,
		private ISystemTagManager $tagManager,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'systemtags';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Tags');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if ($route === 'files.View.index') {
			return -4;
		}
		return 6;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$matchingTags = $this->tagManager->getAllTags(true, $query->getTerm());
		if (count($matchingTags) === 0) {
			return SearchResult::complete($this->l10n->t('Tags'), []);
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$fileQuery = new SearchQuery(
			new SearchComparison(ISearchComparison::COMPARE_LIKE, 'systemtag', '%' . $query->getTerm() . '%'),
			$query->getLimit(),
			(int)$query->getCursor(),
			$query->getSortOrder() === ISearchQuery::SORT_DATE_DESC ? [
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
			] : [],
			$user
		);

		// do search
		$searchResults = $userFolder->search($fileQuery);
		$resultIds = array_map(function (Node $node) {
			return $node->getId();
		}, $searchResults);
		$matchedTags = $this->objectMapper->getTagIdsForObjects($resultIds, 'files');

		// prepare direct tag results
		$tagResults = array_map(function (ISystemTag $tag) {
			$thumbnailUrl = '';
			$link = $this->urlGenerator->linkToRoute('files.view.indexView', [
				'view' => 'tags',
			]) . '?dir=' . $tag->getId();
			$searchResultEntry = new SearchResultEntry(
				$thumbnailUrl,
				$this->l10n->t('All tagged %s …', [$tag->getName()]),
				'',
				$this->urlGenerator->getAbsoluteURL($link),
				'icon-tag'
			);
			return $searchResultEntry;
		}, $matchingTags);

		// prepare files results
		return SearchResult::paginated(
			$this->l10n->t('Tags'),
			array_map(function (Node $result) use ($userFolder, $matchedTags, $query) {
				// Generate thumbnail url
				$thumbnailUrl = $this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['x' => 32, 'y' => 32, 'fileId' => $result->getId()]);
				$path = $userFolder->getRelativePath($result->getPath());

				// Use shortened link to centralize the various
				// files/folder url redirection in files.View.showFile
				$link = $this->urlGenerator->linkToRoute(
					'files.View.showFile',
					['fileid' => $result->getId()]
				);

				$searchResultEntry = new SearchResultEntry(
					$thumbnailUrl,
					$result->getName(),
					$this->formatSubline($query, $matchedTags[$result->getId()]),
					$this->urlGenerator->getAbsoluteURL($link),
					$result->getMimetype() === FileInfo::MIMETYPE_FOLDER ? 'icon-folder' : $this->mimeTypeDetector->mimeTypeIcon($result->getMimetype())
				);
				$searchResultEntry->addAttribute('fileId', (string)$result->getId());
				$searchResultEntry->addAttribute('path', $path);
				return $searchResultEntry;
			}, $searchResults)
			+ $tagResults,
			$query->getCursor() + $query->getLimit()
		);
	}

	/**
	 * Format subline for tagged files: Show the first 3 tags
	 *
	 * @param $query
	 * @param array $tagInfo
	 * @return string
	 */
	private function formatSubline(ISearchQuery $query, array $tagInfo): string {
		/**
		 * @var ISystemTag[]
		 */
		$tags = $this->tagManager->getTagsByIds($tagInfo);
		$tagNames = array_map(function ($tag) {
			return $tag->getName();
		}, array_filter($tags, function ($tag) {
			return $tag->isUserVisible();
		}));

		// show the tag that you have searched for first
		usort($tagNames, function ($tagName) use ($query) {
			return strpos($tagName, $query->getTerm()) !== false? -1 :  1;
		});

		return $this->l10n->t('tagged %s', [implode(', ', array_slice($tagNames, 0, 3))]);
	}

	private function flattenArray($array) {
		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
		return iterator_to_array($it, true);
	}
}
