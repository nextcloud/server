<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CardDAV\Search;

use NCU\Search\AccountScopedSearchResult;
use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\MetadataField;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Search\SearchOperatorEvaluator;
use OCP\Files\Search\ISearchQuery;
use OCP\Files\SimpleFS\InMemoryFile;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

/**
 * @psalm-import-type DeclarativeSettingsFormField from \OCP\Settings\IDeclarativeSettingsForm
 */
class AccountScopedSearchProvider implements IAccountScopedSearchProvider {
	private const ID = 'contacts';

	/** The properties a `content` condition searches. Anything outside this list is not indexed. */
	private const CONTENT_PROPERTIES = ['FN', 'N', 'NICKNAME', 'EMAIL', 'TEL', 'ADR', 'ORG', 'TITLE', 'NOTE', 'CATEGORIES'];

	private const FIELD_PROPERTIES = [
		'content' => self::CONTENT_PROPERTIES,
		'name' => ['FN', 'N', 'NICKNAME'],
		'email' => ['EMAIL'],
		'phone' => ['TEL'],
		'organisation' => ['ORG'],
	];

	/** The account directory, regenerated from user accounts rather than written by a custodian. */
	private const GENERATED_ADDRESS_BOOK_URIS = ['system'];

	/** @var array<string, array<int, array<string, mixed>>> */
	private array $addressBookCache = [];

	public function __construct(
		private readonly IL10N $l10n,
		private readonly CardDavBackend $backend,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Contacts');
	}

	#[\Override]
	public function getSearchCriterion(): array {
		return [
			['id' => 'content', 'title' => $this->l10n->t('Content'), 'type' => 'text', 'default' => ''],
			['id' => 'name', 'title' => $this->l10n->t('Name'), 'type' => 'text', 'default' => ''],
			['id' => 'email', 'title' => $this->l10n->t('Email'), 'type' => 'text', 'default' => ''],
			['id' => 'phone', 'title' => $this->l10n->t('Phone'), 'type' => 'text', 'default' => ''],
			['id' => 'organisation', 'title' => $this->l10n->t('Organisation'), 'type' => 'text', 'default' => ''],
		];
	}

	#[\Override]
	public function getSearchResultMetadataKeys(): array {
		return [
			'owner' => $this->l10n->t('Owner'),
			'addressBookName' => $this->l10n->t('Address book'),
			'modified' => $this->l10n->t('Modified'),
			'checksum' => $this->l10n->t('Checksum'),
			'shared' => $this->l10n->t('Shared'),
			'emails' => $this->l10n->t('Emails'),
			'phones' => $this->l10n->t('Phones'),
			'organisation' => $this->l10n->t('Organisation'),
			'jobTitle' => $this->l10n->t('Job title'),
			'categories' => $this->l10n->t('Categories'),
		];
	}

	#[\Override]
	public function search(string $userId, ISearchQuery $query): \Generator {
		$books = $this->addressBooksFor($userId);
		$operation = $query->getSearchOperation();

		$skipped = 0;
		$yielded = 0;
		foreach ($this->candidates($userId) as $row) {
			$book = $books[(int)($row['addressbookid'] ?? 0)] ?? null;
			$uri = (string)($row['uri'] ?? '');
			if ($book === null || $uri === '') {
				// A hit in the system address book, or one whose book was filtered out.
				continue;
			}

			$card = $this->parse((string)($row['carddata'] ?? ''));
			if ($card === null) {
				continue;
			}

			if (!SearchOperatorEvaluator::matches($operation, fn (string $field): array => $this->valuesFor($card, $field))) {
				continue;
			}

			// Skipped after matching, because the offset counts matches, not candidates. Exact
			// because candidate order is sorted in candidates() rather than left to the backend.
			if ($skipped < $query->getOffset()) {
				$skipped++;

				continue;
			}
			if ($yielded >= $query->getLimit()) {
				return;
			}
			$yielded++;

			$name = (string)($card->FN ?? '');
			$entry = new AccountScopedSearchResult(
				$this->encodeId((string)$book['uri'], $uri),
				$name !== '' ? $name : $uri,
			);

			$this->addMetaData($entry, 'owner', fn (): string => $this->uidOfPrincipal(
				(string)($book['{http://owncloud.org/ns}owner-principal'] ?? $book['principaluri'] ?? '')) ?: $userId);
			$this->addMetaData($entry, 'addressBookName', static fn (): string
				=> (string)($book['{DAV:}displayname'] ?? $book['uri']));
			$this->addMetaData($entry, 'shared', fn (): bool => $this->isShared($book));

			$rev = $this->revTimestamp($card);
			$this->addMetaData($entry, 'modified', static function () use ($rev): int {
				if ($rev === null) {
					throw new \RuntimeException('the card carries no REV property');
				}

				return $rev;
			});

			// Over the stored bytes, which is what readContent() streams. CardDAV keeps no checksum
			// of its own, so this is computed here rather than read.
			$data = (string)($row['carddata'] ?? '');
			$this->addMetaData($entry, 'checksum', static fn (): string => 'SHA256:' . hash('sha256', $data));

			$this->addMetaData($entry, 'emails', fn (): array => $this->propertyValues($card, 'EMAIL'));
			$this->addMetaData($entry, 'phones', fn (): array => $this->propertyValues($card, 'TEL'));
			$this->addMetaData($entry, 'categories', fn (): array => $this->propertyValues($card, 'CATEGORIES'));

			$organisation = $this->propertyValues($card, 'ORG');
			$this->addMetaData($entry, 'organisation', static function () use ($organisation): string {
				if ($organisation === []) {
					throw new \RuntimeException('no organisation recorded on this card');
				}

				return $organisation[0];
			});

			$jobTitle = $this->propertyValues($card, 'TITLE');
			$this->addMetaData($entry, 'jobTitle', static function () use ($jobTitle): string {
				if ($jobTitle === []) {
					throw new \RuntimeException('no job title recorded on this card');
				}

				return $jobTitle[0];
			});

			yield $entry;
		}
	}

	/**
	 * Attach one field, or record why it could not be read. A read failure never drops the whole
	 * entry — the search result is still yielded and the gap is visible on the field itself.
	 */
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

	#[\Override]
	public function readContent(string $userId, AccountScopedSearchResult $entry): ?ISimpleFile {
		$decoded = $this->decodeId($entry->getId());
		if ($decoded === null) {
			return null;
		}
		[$bookUri, $cardUri] = $decoded;

		$book = $this->findAddressBook($userId, $bookUri);
		$data = $book === null ? null : $this->cardData((int)$book['id'], $cardUri);
		if ($data === null) {
			return null;
		}

		$card = $this->parse($data);
		$name = $card === null ? '' : (string)($card->FN ?? '');

		return new InMemoryFile($this->safeName($name !== '' ? $name : $cardUri) . '.vcf', $data);
	}

	/**
	 * Every card in the account's address books, deliberately not narrowed by the property index —
	 * see the class docblock on `SearchOperatorEvaluator`.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function candidates(string $userId): array {
		try {
			$rows = $this->backend->searchPrincipalUri(
				'principals/users/' . $userId,
				'',
				self::CONTENT_PROPERTIES,
				['wildcard' => true],
			);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Could not read contacts for ' . $userId, 0, $e);
		}

		$found = [];
		foreach ($rows as $row) {
			$found[$row['addressbookid'] . ':' . $row['uri']] = $row;
		}

		// Ordered by the card's own identity, so resuming an account skips the same matches an
		// interrupted call already yielded. CardDAV promises no order of its own.
		ksort($found);

		return array_values($found);
	}

	/**
	 * The account's own and shared-with address books, keyed by id.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function addressBooksFor(string $userId): array {
		if (isset($this->addressBookCache[$userId])) {
			return $this->addressBookCache[$userId];
		}

		try {
			$books = $this->backend->getAddressBooksForUser('principals/users/' . $userId);
		} catch (\Throwable $e) {
			$this->logger->warning('Could not list address books for an account', ['exception' => $e, 'userId' => $userId]);

			return [];
		}

		$byId = [];
		foreach ($books as $book) {
			if (in_array((string)($book['uri'] ?? ''), self::GENERATED_ADDRESS_BOOK_URIS, true)) {
				continue;
			}
			$byId[(int)$book['id']] = $book;
		}

		$this->addressBookCache[$userId] = $byId;

		return $byId;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function findAddressBook(string $userId, string $uri): ?array {
		foreach ($this->addressBooksFor($userId) as $book) {
			if ((string)$book['uri'] === $uri) {
				return $book;
			}
		}

		return null;
	}

	private function cardData(int $addressBookId, string $cardUri): ?string {
		if ($cardUri === '') {
			return null;
		}

		try {
			$card = $this->backend->getCard($addressBookId, $cardUri);
		} catch (\Throwable $e) {
			$this->logger->debug('Contact card could not be read', ['exception' => $e, 'cardUri' => $cardUri]);

			return null;
		}

		$data = is_array($card) ? ($card['carddata'] ?? null) : null;

		return is_string($data) && $data !== '' ? $data : null;
	}

	private function parse(string $data): ?VCard {
		if ($data === '') {
			return null;
		}

		try {
			$card = Reader::read($data);
		} catch (\Throwable $e) {
			$this->logger->warning('Unparseable vCard skipped', ['exception' => $e]);

			return null;
		}

		return $card instanceof VCard ? $card : null;
	}

	/**
	 * @return list<mixed>
	 */
	private function valuesFor(VCard $card, string $field): array {
		$values = [];
		foreach (self::FIELD_PROPERTIES[$field] ?? [] as $name) {
			foreach ($card->select($name) as $property) {
				$value = (string)$property;
				if ($value !== '') {
					$values[] = $value;
				}
			}
		}

		return $values;
	}

	/**
	 * @return list<string>
	 */
	private function propertyValues(VCard $card, string $property): array {
		$found = [];
		foreach ($card->select($property) as $entry) {
			$value = trim((string)$entry);
			if ($value !== '') {
				$found[] = $value;
			}
		}

		return $found;
	}

	private function revTimestamp(VCard $card): ?int {
		$rev = (string)($card->REV ?? '');
		if ($rev === '') {
			return null;
		}
		$time = strtotime($rev);

		return $time === false ? null : $time;
	}

	/**
	 * @param array<string, mixed> $book
	 */
	private function isShared(array $book): bool {
		$owner = (string)($book['{http://owncloud.org/ns}owner-principal'] ?? '');
		$principal = (string)($book['principaluri'] ?? '');
		if ($owner !== '' && $owner !== $principal) {
			return true;
		}

		try {
			return $this->backend->getShares((int)$book['id']) !== [];
		} catch (\Throwable) {
			return false;
		}
	}

	private function uidOfPrincipal(string $principalUri): string {
		return str_starts_with($principalUri, 'principals/users/')
			? substr($principalUri, strlen('principals/users/'))
			: '';
	}

	private function safeName(string $name): string {
		return trim(preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $name) ?? '', '_') ?: 'contact';
	}

	private function encodeId(string $bookUri, string $cardUri): string {
		return rawurlencode($bookUri) . ':' . $cardUri;
	}

	/**
	 * @return array{0: string, 1: string}|null
	 */
	private function decodeId(string $id): ?array {
		$parts = explode(':', $id, 2);
		if (count($parts) !== 2) {
			return null;
		}

		return [rawurldecode($parts[0]), $parts[1]];
	}
}
