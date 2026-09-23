<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\Exceptions\AccountUnavailableException;
use NCU\Search\Exceptions\SearchTruncatedException;
use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\SearchPropertyDefinition;
use NCU\Search\SearchPropertyType;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\SimpleFS\SimpleFile;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\User\Exceptions\UserNotFoundException;
use Psr\Log\LoggerInterface;

class FileAccountScopedSearchProvider implements IAccountScopedSearchProvider {
	private const ID = 'files';

	private const SHARE_PAGE = 50;

	/** Share types that mean the content left the organisation. */
	private const EXTERNAL_SHARE_TYPES = [
		IShare::TYPE_LINK,
		IShare::TYPE_EMAIL,
		IShare::TYPE_REMOTE,
		IShare::TYPE_REMOTE_GROUP,
	];

	/** Above this many content matches, the search is refused as truncated. */
	private const CONTENT_MATCH_LIMIT = 5000;

	/** Property names that differ from the filecache field they are searched on. */
	private const FIELD_COLUMNS = [
		'modified' => 'mtime',
		'created' => 'creation_time',
	];

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
			new SearchPropertyDefinition('shared_externally', $this->l10n->t('Shared externally'), SearchPropertyType::Boolean, searchable: true),
			new SearchPropertyDefinition('mimetype', $this->l10n->t('File type'), searchable: true, selectable: true),
			new SearchPropertyDefinition('size', $this->l10n->t('Size'), SearchPropertyType::Integer, searchable: true, selectable: true),
			new SearchPropertyDefinition('modified', $this->l10n->t('Modified'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('created', $this->l10n->t('Created'), SearchPropertyType::DateTime, searchable: true, selectable: true),
			new SearchPropertyDefinition('mount_point', $this->l10n->t('Mount point'), selectable: true),
			new SearchPropertyDefinition('checksum', $this->l10n->t('Checksum'), selectable: true),
			new SearchPropertyDefinition('share_status', $this->l10n->t('Share status'), SearchPropertyType::Object, selectable: true, detailOnly: true),
			new SearchPropertyDefinition('shared_with', $this->l10n->t('Shared with'), selectable: true, detailOnly: true),
			new SearchPropertyDefinition('tags', $this->l10n->t('Tags'), selectable: true, detailOnly: true),
		];

		// Only offered when an index can answer it.
		if ($this->indexAvailable()) {
			$properties[] = new SearchPropertyDefinition('content', $this->l10n->t('Content'), searchable: true, indexed: true);
		}

		return $properties;
	}

	/**
	 * Not cached, as the index can be installed or removed at any time.
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
	public function search(string $userId, ?ISearchOperator $filter, int $limit, int $offset = 0): \Generator {
		$userFolder = $this->userFolder($userId);

		// A folder is not an item: its files are found on their own.
		$operators = [
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', FileInfo::MIMETYPE_FOLDER),
			]),
		];
		if ($filter !== null) {
			$contentMatches = $this->resolveContentTerms($filter, $userId, []);
			$externallyShared = $this->usesField($filter, 'shared_externally')
				? $this->externallySharedFileIds($userId)
				: [];
			$operators[] = $this->resolveOperator($filter, $contentMatches, $externallyShared);
		}

		$query = new SearchQuery(
			new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, $operators),
			$limit,
			$offset,
			[new SearchOrder(ISearchOrder::DIRECTION_ASCENDING, 'fileid')],
			null,
			false,
			['creation_time'],
		);

		foreach ($userFolder->search($query) as $node) {
			if ($node instanceof File) {
				yield $this->entryFor($userFolder, $node, false);
			}
		}
	}

	#[\Override]
	public function get(string $userId, string $id): ?AccountScopedSearchResult {
		$userFolder = $this->userFolder($userId);
		$node = $this->findFile($userFolder, $id);

		return $node === null ? null : $this->entryFor($userFolder, $node, true);
	}

	#[\Override]
	public function readContent(string $userId, string $id): ?ISimpleFile {
		$node = $this->findFile($this->userFolder($userId), $id);

		return $node === null ? null : new SimpleFile($node);
	}

	/**
	 * @throws AccountUnavailableException
	 */
	private function userFolder(string $userId): Folder {
		try {
			return $this->rootFolder->getUserFolder($userId);
		} catch (UserNotFoundException|NotPermittedException $e) {
			throw new AccountUnavailableException($e->getMessage(), 0, $e);
		}
	}

	private function findFile(Folder $userFolder, string $id): ?File {
		if (!ctype_digit($id)) {
			return null;
		}

		$node = $userFolder->getFirstNodeById((int)$id);

		return $node instanceof File ? $node : null;
	}

	/**
	 * @param bool $detail Whether to also read the detail-only properties
	 */
	private function entryFor(Folder $userFolder, File $node, bool $detail): AccountScopedSearchResult {
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

		$this->addMetaData($entry, 'mount_point', static fn (): string => $node->getMountPoint()->getMountPoint());

		// Stored as "TYPE:VALUE", and only when a client supplied one on upload.
		$this->addMetaData($entry, 'checksum', static function () use ($node): string {
			$checksum = $node->getChecksum();
			if ($checksum === '') {
				throw new \RuntimeException('no checksum stored for this file');
			}

			return $checksum;
		});

		if (!$detail) {
			return $entry;
		}

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

		return $entry;
	}

	/**
	 * Every distinct `content` term in a tree, resolved once each.
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
			$term = $this->contentTerm($operator);
			if (!array_key_exists($term, $resolved)) {
				$resolved[$term] = $this->contentMatchedFileIds($term, $userId);
			}
		}

		return $resolved;
	}

	private function usesField(ISearchOperator $operator, string $field): bool {
		if ($operator instanceof ISearchBinaryOperator) {
			foreach ($operator->getArguments() as $child) {
				if ($this->usesField($child, $field)) {
					return true;
				}
			}

			return false;
		}

		return $operator instanceof ISearchComparison && $operator->getField() === $field;
	}

	/**
	 * Rewrite a tree into filecache fields, with `content` and `shared_externally` resolved to fileids.
	 *
	 * @param array<string, list<int>> $contentMatches
	 * @param list<int> $externallyShared
	 */
	private function resolveOperator(ISearchOperator $operator, array $contentMatches, array $externallyShared): ISearchOperator {
		if ($operator instanceof ISearchBinaryOperator) {
			return new SearchBinaryOperator(
				$operator->getType(),
				array_map(fn (ISearchOperator $child): ISearchOperator
					=> $this->resolveOperator($child, $contentMatches, $externallyShared), $operator->getArguments()),
			);
		}

		if (!$operator instanceof ISearchComparison) {
			return $operator;
		}

		if ($operator->getField() === 'content') {
			return $this->fileIdsToComparison($contentMatches[$this->contentTerm($operator)]);
		}

		if ($operator->getField() === 'shared_externally') {
			if ($operator->getType() !== ISearchComparison::COMPARE_EQUAL) {
				throw new \InvalidArgumentException('Unsupported comparison for field shared_externally: ' . $operator->getType());
			}

			$ids = $this->fileIdsToComparison($externallyShared);

			return filter_var($operator->getValue(), FILTER_VALIDATE_BOOL)
				? $ids
				: new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_NOT, [$ids]);
		}

		// The filecache path is relative to the storage root. A file received through a share is
		// stored under the owner's path, so the recipient's path does not match it.
		if ($operator->getField() === 'path') {
			$value = $operator->getValue();

			return new SearchComparison(
				$operator->getType(),
				'path',
				is_array($value)
					? array_map(static fn (mixed $path): string => 'files/' . ltrim((string)$path, '/'), $value)
					: 'files/' . ltrim((string)$value, '/'),
				$operator->getExtra(),
			);
		}

		if (isset(self::FIELD_COLUMNS[$operator->getField()])) {
			return new SearchComparison(
				$operator->getType(),
				self::FIELD_COLUMNS[$operator->getField()],
				$operator->getValue(),
				$operator->getExtra(),
			);
		}

		return $operator;
	}

	/**
	 * The text a `content` comparison searches for. The index always matches a term anywhere in
	 * the content, so the wildcards of a LIKE pattern are dropped.
	 */
	private function contentTerm(ISearchComparison $comparison): string {
		$value = (string)$comparison->getValue();
		if (!in_array($comparison->getType(), [ISearchComparison::COMPARE_LIKE, ISearchComparison::COMPARE_LIKE_CASE_SENSITIVE], true)) {
			return $value;
		}

		$term = preg_replace('/(?<!\\\\)[%_]/', ' ', $value) ?? $value;

		return trim(str_replace(['\\%', '\\_', '\\\\'], ['%', '_', '\\'], $term));
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
			throw new \RuntimeException('The content index did not answer for ' . $userId, 0, $e);
		}

		$fileIds = [];
		foreach ($results as $result) {
			foreach ($result->getDocuments() as $document) {
				$fileIds[(int)$document->getId()] = true;
			}
		}

		// ISearchResult exposes no total, so a full page may be a subset.
		if (count($fileIds) >= self::CONTENT_MATCH_LIMIT) {
			throw new SearchTruncatedException(
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
			if ($value === null) {
				$entry->setMetadataError($name, 'not reported by the storage');
			} else {
				$entry->setMetadata($name, $value);
			}
		} catch (\Throwable $e) {
			$entry->setMetadataError($name, $e->getMessage());
		}
	}

	/**
	 * Fileids of everything the account shared outside the organisation.
	 *
	 * @return list<int>
	 */
	private function externallySharedFileIds(string $userId): array {
		$fileIds = [];
		foreach (self::EXTERNAL_SHARE_TYPES as $shareType) {
			$offset = 0;
			do {
				$page = $this->shareManager->getSharesBy($userId, $shareType, null, false, self::SHARE_PAGE, $offset);
				foreach ($page as $share) {
					$fileIds[$share->getNodeId()] = true;
				}
				$offset += count($page);
			} while (count($page) === self::SHARE_PAGE);
		}

		return array_keys($fileIds);
	}

	/**
	 * @return array{shared: bool, externally: bool, shares: list<array{type: int, external: bool, recipient: ?string, shareId: string}>}
	 */
	private function shareStatus(File $node): array {
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
						// Not the token: a public-link token is a credential.
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
	 * The user-visible tags on this file.
	 *
	 * @return list<string>
	 */
	private function visibleTags(File $node): array {
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
}
