<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\MetadataField;
use NCU\Search\SearchPropertyDefinition;
use NCU\Search\SearchPropertyType;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\SimpleFS\SimpleFile;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchQuery;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;

class FileAccountScopedSearchProvider implements IAccountScopedSearchProvider {
	private const ID = 'files';

	/** Page size for share enumeration. */
	private const SHARE_PAGE = 50;

	/** Share types that mean the content left the organisation. */
	private const EXTERNAL_SHARE_TYPES = [
		IShare::TYPE_LINK,
		IShare::TYPE_EMAIL,
		IShare::TYPE_REMOTE,
		IShare::TYPE_REMOTE_GROUP,
	];

	/** How many content matches to read from the index before giving up on answering exhaustively. */
	private const CONTENT_MATCH_LIMIT = 5000;

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IRootFolder $rootFolder,
		private readonly IShareManager $shareManager,
		private readonly ISystemTagObjectMapper $tagObjectMapper,
		private readonly ISystemTagManager $tagManager,
		private readonly IFullTextSearchManager $fullTextSearchManager,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Files');
	}

	#[\Override]
	public function getProperties(): array {
		$properties = [
			new SearchPropertyDefinition('name', $this->l10n->t('Name'), searchable: true),
			new SearchPropertyDefinition('owner', $this->l10n->t('Owner'), selectable: true),
			new SearchPropertyDefinition('path', $this->l10n->t('Path'), searchable: true, selectable: true),
			new SearchPropertyDefinition('mimetype', $this->l10n->t('File type'), searchable: true, selectable: true),
			new SearchPropertyDefinition('size', $this->l10n->t('Size'), SearchPropertyType::Integer, searchable: true, selectable: true),
			new SearchPropertyDefinition('modified', $this->l10n->t('Modified'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('created', $this->l10n->t('Created'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('checksum', $this->l10n->t('Checksum'), selectable: true),
			new SearchPropertyDefinition('share_status', $this->l10n->t('Share status'), SearchPropertyType::Object, selectable: true),
			new SearchPropertyDefinition('shared_with', $this->l10n->t('Shared with'), selectable: true, multiValued: true),
			new SearchPropertyDefinition('tags', $this->l10n->t('Tags'), selectable: true, multiValued: true),
		];

		// Offered only when an index can actually answer it. Advertising `content` without one
		// would let a caller build a search that quietly matches nothing.
		if ($this->indexAvailable()) {
			$properties[] = new SearchPropertyDefinition('content', $this->l10n->t('Content'), searchable: true);
		}

		return $properties;
	}

	/**
	 * Checked per call rather than cached: an administrator can install or remove the index without
	 * restarting anything, and a search that claims to have read content when it did not is the
	 * worst answer this can give.
	 */
	private function indexAvailable(): bool {
		try {
			if (!$this->fullTextSearchManager->isAvailable()
				|| !$this->fullTextSearchManager->isProviderIndexed(self::ID)) {
				return false;
			}

			return $this->appConfig->getValueString('fulltextsearch', 'search_platform', '') !== '';
		} catch (\Throwable $e) {
			$this->logger->debug('Fulltextsearch availability check failed', ['exception' => $e]);

			return false;
		}
	}

	#[\Override]
	public function search(string $userId, ISearchQuery $query): \Generator {
		$userFolder = $this->rootFolder->getUserFolder($userId);

		$contentMatches = $this->resolveContentTerms($query->getSearchOperation(), $userId, []);
		$resolvedQuery = new SearchQuery(
			$this->substituteContent($query->getSearchOperation(), $contentMatches),
			$query->getLimit(),
			$query->getOffset(),
			$query->getOrder(),
			$query->getUser(),
			$query->limitToHome(),
			array_unique([...$query->getSelectFields(), 'creation_time']),
		);

		foreach ($userFolder->search($resolvedQuery) as $node) {
			$entry = new AccountScopedSearchResult((string)$node->getId(), $node->getName());

			$this->addMetaData($entry, 'owner', static fn (): ?string => $node->getOwner()?->getUID());
			$this->addMetaData($entry, 'path', static fn (): string => $userFolder->getRelativePath($node->getPath()) ?? $node->getPath());
			$this->addMetaData($entry, 'mimetype', static fn (): string => $node->getMimetype());
			$this->addMetaData($entry, 'size', static fn (): int|float => $node->getSize());
			$this->addMetaData($entry, 'modified', static fn (): int => $node->getMTime());

			$this->addMetaData($entry, 'created', static function () use ($node): int {
				$created = $node->getCreationTime();
				if ($created === 0) {
					throw new \RuntimeException('creation time not recorded by the storage');
				}

				return $created;
			});

			// Nextcloud stores checksums as "TYPE:VALUE" and only when a client supplied one on
			// upload; there is no stored SHA-256.
			$this->addMetaData($entry, 'checksum', static function () use ($node): string {
				$checksum = $node instanceof File ? $node->getChecksum() : '';
				if ($checksum === '') {
					throw new \RuntimeException('no checksum stored for this file');
				}

				return $checksum;
			});

			$shareStatus = null;
			$this->addMetaData($entry, 'share_status', function () use ($node, &$shareStatus): array {
				$shareStatus = $this->shareStatus($node);

				return $shareStatus;
			});
			$this->addMetaData($entry, 'shared_with', static function () use (&$shareStatus): array {
				if ($shareStatus === null) {
					throw new \RuntimeException('share status not captured');
				}

				$recipients = [];
				foreach ($shareStatus['shares'] as $share) {
					$recipient = $share['recipient'] ?? '';
					if ($recipient !== '') {
						$recipients[] = $recipient;
					}
				}

				return array_values(array_unique($recipients));
			});

			$this->addMetaData($entry, 'tags', fn (): array => $this->visibleTags($node));

			yield $entry;
		}
	}

	/**
	 * Every distinct `content` term in a tree, resolved once each — the same term can appear more
	 * than once (e.g. under both branches of an `or`), and each occurrence means the same set, so
	 * resolving it twice would be an identical Fulltextsearch round-trip for no reason.
	 *
	 * @param array<string, list<int>> $resolved accumulated so far
	 * @return array<string, list<int>>
	 */
	private function resolveContentTerms(ISearchOperator $operator, string $userId, array $resolved): array {
		if ($operator instanceof ISearchBinaryOperator) {
			foreach ($operator->getArguments() as $child) {
				$resolved = $this->resolveContentTerms($child, $userId, $resolved);
			}

			return $resolved;
		}

		if ($operator instanceof ISearchComparison && $operator->getField() === 'content') {
			$term = (string)$operator->getValue();
			if (!array_key_exists($term, $resolved)) {
				$resolved[$term] = $this->contentMatchedFileIds($term, $userId);
			}
		}

		return $resolved;
	}

	/**
	 * Rewrite every `content` comparison in a tree into the fileids it resolved to.
	 *
	 * @param array<string, list<int>> $contentMatches
	 */
	private function substituteContent(ISearchOperator $operator, array $contentMatches): ISearchOperator {
		if ($operator instanceof ISearchBinaryOperator) {
			return new SearchBinaryOperator(
				$operator->getType(),
				array_map(fn (ISearchOperator $child): ISearchOperator
					=> $this->substituteContent($child, $contentMatches), $operator->getArguments()),
			);
		}

		if ($operator instanceof ISearchComparison && $operator->getField() === 'content') {
			return $this->fileIdsToComparison($contentMatches[(string)$operator->getValue()]);
		}

		return $operator;
	}

	/**
	 * Fileids the index says contain one term, for one account.
	 *
	 * @return list<int>
	 */
	private function contentMatchedFileIds(string $term, string $userId): array {
		try {
			$results = $this->fullTextSearchManager->search([
				'providers' => [self::ID],
				'search' => $term,
				'size' => self::CONTENT_MATCH_LIMIT,
			], $userId);
		} catch (\Throwable $e) {
			// An index that cannot answer must not silently narrow the search to nothing while the
			// caller reads a result that looks complete.
			throw new \RuntimeException('The content index did not answer for ' . $userId, 0, $e);
		}

		$fileIds = [];
		foreach ($results as $result) {
			foreach ($result->getDocuments() as $document) {
				$fileIds[(int)$document->getId()] = true;
			}
		}

		// Answering *partially* is just as wrong as not answering: the id set would then be a
		// subset, and matching on it would silently discard files that genuinely contain the term.
		// ISearchResult exposes no total, so a full page is the only signal available.
		if (count($fileIds) >= self::CONTENT_MATCH_LIMIT) {
			throw new \RuntimeException(
				'Content search for "' . $term . '" matched at least ' . self::CONTENT_MATCH_LIMIT
				. ' files for ' . $userId . ' and cannot be answered exhaustively',
			);
		}

		return array_keys($fileIds);
	}

	/**
	 * A set of fileids as a filecache condition.
	 *
	 * @param list<int> $ids
	 */
	private function fileIdsToComparison(array $ids): ISearchOperator {
		if ($ids === []) {
			return new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'fileid', -1);
		}

		return new SearchComparison(ISearchComparison::COMPARE_IN, 'fileid', $ids);
	}

	private function addMetaData(AccountScopedSearchResult $entry, string $name, callable $read): void {
		try {
			$value = $read();
			$entry->addMetaData($value === null
				? MetadataField::notCaptured($name, 'not reported by the storage')
				: MetadataField::captured($name, $value));
		} catch (\Throwable $e) {
			$entry->addMetaData(MetadataField::notCaptured($name, $e->getMessage()));
		}
	}

	/**
	 * @return array{shared: bool, externally: bool, shares: list<array{type: int, external: bool, recipient: ?string, shareId: string}>}
	 */
	private function shareStatus(Node $node): array {
		$owner = $node->getOwner()?->getUID();
		if ($owner === null) {
			throw new \RuntimeException('owner unknown, so shares cannot be enumerated');
		}

		$externally = false;
		$entries = [];
		foreach ([...self::EXTERNAL_SHARE_TYPES, IShare::TYPE_USER, IShare::TYPE_GROUP] as $shareType) {
			$external = in_array($shareType, self::EXTERNAL_SHARE_TYPES, true);
			$offset = 0;
			do {
				$page = $this->shareManager->getSharesBy($owner, $shareType, $node, true, self::SHARE_PAGE, $offset);
				foreach ($page as $share) {
					$externally = $externally || $external;
					$entries[] = [
						'type' => $shareType,
						'external' => $external,
						'recipient' => $share->getSharedWith(),
						// Deliberately NOT the token: a public-link token is a bearer credential, and
						// this entry may end up somewhere longer-lived than the search response.
						'shareId' => $share->getId(),
					];
				}
				$offset += count($page);
			} while (count($page) === self::SHARE_PAGE);
		}

		return [
			'shared' => $entries !== [],
			'externally' => $externally,
			'shares' => $entries,
		];
	}

	/**
	 * The tags on this file that anyone is allowed to see. An invisible tag (`userVisible=false`) is
	 * hidden by the platform from everyone but administrators, and must stay that way here too.
	 *
	 * @return list<string>
	 */
	private function visibleTags(Node $node): array {
		$objectId = (string)$node->getId();
		$assigned = $this->tagObjectMapper->getTagIdsForObjects([$objectId], 'files');
		$tagIds = $assigned[$objectId] ?? [];
		if ($tagIds === []) {
			return [];
		}

		try {
			$tags = $this->tagManager->getTagsByIds($tagIds);
		} catch (\Throwable $e) {
			$this->logger->debug('Could not read system tags for a search result', [
				'exception' => $e,
				'fileId' => $objectId,
			]);

			return [];
		}

		$names = [];
		foreach ($tags as $tag) {
			if ($tag->isUserVisible()) {
				$names[] = $tag->getName();
			}
		}

		return $names;
	}

	#[\Override]
	public function readContent(string $userId, AccountScopedSearchResult $entry): ?ISimpleFile {
		$id = $entry->getId();
		if (!ctype_digit($id)) {
			return null;
		}

		try {
			$node = $this->rootFolder->getUserFolder($userId)->getFirstNodeById((int)$id);
		} catch (\Throwable) {
			return null;
		}

		if (!$node instanceof File) {
			return null;
		}

		return new SimpleFile($node);
	}
}
