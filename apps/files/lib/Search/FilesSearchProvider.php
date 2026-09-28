<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Search;

use InvalidArgumentException;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Search\Filter\GroupFilter;
use OC\Search\Filter\UserFilter;
use OCP\Files\FileInfo;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\FilterDefinition;
use OCP\Search\IFilter;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\Share\IShare;

class FilesSearchProvider implements IFilteringProvider {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IMimeTypeDetector $mimeTypeDetector,
		private IRootFolder $rootFolder,
		private IPreview $previewManager,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'files';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Files');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if ($route === 'files.View.index') {
			// Before comments
			return -5;
		}
		return 5;
	}

	public function getSupportedFilters(): array {
		return [
			'term',
			'since',
			'until',
			'person',
			'min-size',
			'max-size',
			'mime',
			'type',
			'path',
			'is-favorite',
			'title-only',
		];
	}

	public function getAlternateIds(): array {
		return [];
	}

	public function getCustomFilters(): array {
		return [
			new FilterDefinition('min-size', FilterDefinition::TYPE_INT),
			new FilterDefinition('max-size', FilterDefinition::TYPE_INT),
			new FilterDefinition('mime', FilterDefinition::TYPE_STRING),
			new FilterDefinition('type', FilterDefinition::TYPE_STRING),
			new FilterDefinition('path', FilterDefinition::TYPE_STRING),
			new FilterDefinition('is-favorite', FilterDefinition::TYPE_BOOL),
		];
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$fileQuery = $this->buildSearchQuery($query, $user);
		return SearchResult::paginated(
			$this->l10n->t('Files'),
			array_map(function (Node $result) use ($userFolder) {
				$thumbnailUrl = $this->previewManager->isMimeSupported($result->getMimetype())
					? $this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['x' => 32, 'y' => 32, 'fileId' => $result->getId()])
					: '';
				$icon = $result->getMimetype() === FileInfo::MIMETYPE_FOLDER
					? 'icon-folder'
					: $this->mimeTypeDetector->mimeTypeIcon($result->getMimetype());
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
					$this->formatSubline($path),
					$this->urlGenerator->getAbsoluteURL($link),
					$icon,
				);
				$searchResultEntry->addAttribute('fileId', (string)$result->getId());
				$searchResultEntry->addAttribute('path', $path);
				return $searchResultEntry;
			}, $userFolder->search($fileQuery)),
			$query->getCursor() + $query->getLimit()
		);
	}

	private function buildSearchQuery(ISearchQuery $query, IUser $user): SearchQuery {
		$comparisons = [];
		foreach ($query->getFilters() as $name => $filter) {
			$comparisons[] = match ($name) {
				'term' => new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%' . $filter->get() . '%'),
				'since' => new SearchComparison(ISearchComparison::COMPARE_GREATER_THAN_EQUAL, 'mtime', $filter->get()->getTimestamp()),
				'until' => new SearchComparison(ISearchComparison::COMPARE_LESS_THAN_EQUAL, 'mtime', $filter->get()->getTimestamp()),
				'min-size' => new SearchComparison(ISearchComparison::COMPARE_GREATER_THAN_EQUAL, 'size', $filter->get()),
				'max-size' => new SearchComparison(ISearchComparison::COMPARE_LESS_THAN_EQUAL, 'size', $filter->get()),
				'mime' => new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', $filter->get()),
				'type' => new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', $filter->get() . '/%'),
				'path' => new SearchComparison(ISearchComparison::COMPARE_LIKE, 'path', 'files/' . ltrim($filter->get(), '/') . '%'),
				'person' => $this->buildPersonSearchQuery($filter),
				default => throw new InvalidArgumentException('Unsupported comparison'),
			};
		}

		return new SearchQuery(
			new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_AND, $comparisons),
			$query->getLimit(),
			(int)$query->getCursor(),
			$query->getSortOrder() === ISearchQuery::SORT_DATE_DESC
				? [new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime')]
				: [],
			$user
		);
	}

	private function buildPersonSearchQuery(IFilter $person): ISearchOperator {
		if ($person instanceof UserFilter) {
			return new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_OR, [
				new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_AND, [
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'share_with', $person->get()->getUID()),
					new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'share_type', IShare::TYPE_USER),
				]),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'owner', $person->get()->getUID()),
			]);
		}
		if ($person instanceof GroupFilter) {
			return new SearchBinaryOperator(SearchBinaryOperator::OPERATOR_AND, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'share_with', $person->get()->getGID()),
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'share_type', IShare::TYPE_GROUP),
			]);
		}

		throw new InvalidArgumentException('Unsupported filter type');
	}

	/**
	 * Format subline for files
	 *
	 * @param string $path
	 * @return string
	 */
	private function formatSubline(string $path): string {
		// Do not show the location if the file is in root
		if (strrpos($path, '/') > 0) {
			$path = ltrim(dirname($path), '/');
			return $this->l10n->t('in %s', [$path]);
		} else {
			return '';
		}
	}
}
