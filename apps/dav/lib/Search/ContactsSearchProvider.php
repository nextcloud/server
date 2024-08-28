<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Search;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\FilterDefinition;
use OCP\Search\IFilter;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

class ContactsSearchProvider implements IFilteringProvider {
	private static array $searchPropertiesRestricted = [
		'N',
		'FN',
		'NICKNAME',
		'EMAIL',
	];

	private static array $searchProperties = [
		'N',
		'FN',
		'NICKNAME',
		'EMAIL',
		'TEL',
		'ADR',
		'TITLE',
		'ORG',
		'NOTE',
	];

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private CardDavBackend $backend,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'contacts';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Contacts');
	}

	public function getOrder(string $route, array $routeParameters): ?int {
		if ($this->appManager->isEnabledForUser('contacts')) {
			return $route === 'contacts.Page.index' ? -1 : 25;
		}

		return null;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser('contacts', $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$principalUri = 'principals/users/' . $user->getUID();
		$addressBooks = $this->backend->getAddressBooksForUser($principalUri);
		$addressBooksById = [];
		foreach ($addressBooks as $addressBook) {
			$addressBooksById[(int)$addressBook['id']] = $addressBook;
		}

		$searchResults = $this->backend->searchPrincipalUri(
			$principalUri,
			$query->getFilter('term')?->get() ?? '',
			$query->getFilter('title-only')?->get() ? self::$searchPropertiesRestricted : self::$searchProperties,
			[
				'limit' => $query->getLimit(),
				'offset' => $query->getCursor(),
				'since' => $query->getFilter('since'),
				'until' => $query->getFilter('until'),
				'person' => $this->getPersonDisplayName($query->getFilter('person')),
				'company' => $query->getFilter('company'),
			],
		);
		$formattedResults = \array_map(function (array $contactRow) use ($addressBooksById):SearchResultEntry {
			$addressBook = $addressBooksById[$contactRow['addressbookid']];

			/** @var VCard $vCard */
			$vCard = Reader::read($contactRow['carddata']);
			$thumbnailUrl = '';
			if ($vCard->PHOTO) {
				$thumbnailUrl = $this->getDavUrlForContact($addressBook['principaluri'], $addressBook['uri'], $contactRow['uri']) . '?photo';
			}

			$title = (string)$vCard->FN;
			$subline = $this->generateSubline($vCard);
			$resourceUrl = $this->getDeepLinkToContactsApp($addressBook['uri'], (string)$vCard->UID);

			$result = new SearchResultEntry($thumbnailUrl, $title, $subline, $resourceUrl, 'icon-contacts-dark', true);
			$result->addAttribute('displayName', $title);
			$result->addAttribute('email', $subline);
			$result->addAttribute('phoneNumber', (string)$vCard->TEL);

			return $result;
		}, $searchResults);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$query->getCursor() + count($formattedResults)
		);
	}
	private function getPersonDisplayName(?IFilter $person): ?string {
		$user = $person?->get();
		if ($user instanceof IUser) {
			return $user->getDisplayName();
		}
		return null;
	}

	protected function getDavUrlForContact(
		string $principalUri,
		string $addressBookUri,
		string $contactsUri,
	): string {
		[, $principalType, $principalId] = explode('/', $principalUri, 3);

		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkTo('', 'remote.php') . '/dav/addressbooks/'
				. $principalType . '/'
				. $principalId . '/'
				. $addressBookUri . '/'
				. $contactsUri
		);
	}

	protected function getDeepLinkToContactsApp(
		string $addressBookUri,
		string $contactUid,
	): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute('contacts.contacts.direct', [
				'contact' => $contactUid . '~' . $addressBookUri
			])
		);
	}

	protected function generateSubline(VCard $vCard): string {
		$emailAddresses = $vCard->select('EMAIL');
		if (!is_array($emailAddresses) || empty($emailAddresses)) {
			return '';
		}

		return (string)$emailAddresses[0];
	}

	public function getSupportedFilters(): array {
		return [
			'term',
			'since',
			'until',
			'person',
			'title-only',
		];
	}

	public function getAlternateIds(): array {
		return [];
	}

	public function getCustomFilters(): array {
		return [
			new FilterDefinition('company'),
		];
	}
}
