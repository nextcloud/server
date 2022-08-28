<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			$addressBooksById[(int) $addressBook['id']] = $addressBook;
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
			$resourceUrl = $this->getDeepLinkToContactsApp($addressBook['uri'], (string) $vCard->UID);

			return new SearchResultEntry($thumbnailUrl, $title, $subline, $resourceUrl, 'icon-contacts-dark', true);
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
