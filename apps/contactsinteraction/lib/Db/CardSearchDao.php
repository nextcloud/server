<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContactsInteraction\Db;

use OCA\DAV\CardDAV\SystemAddressBookVisibility;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;
use function is_resource;
use function stream_get_contents;

class CardSearchDao {

	public function __construct(
		private IDBConnection $db,
		private IUserManager $userManager,
		private SystemAddressBookVisibility $systemAddressBookVisibility,
	) {
	}

	/**
	 * Find a matching card in the address books of $user, or in the system
	 * address book if that card is visible to $user
	 */
	public function findExisting(IUser $user,
		?string $uid,
		?string $email,
		?string $cloudId): ?string {
		$cardQuery = $this->getMatchingCardsQuery('principals/users/' . $user->getUID(), null, $uid, $email, $cloudId);
		$cardQuery->setMaxResults(1);
		$result = $cardQuery->executeQuery();
		/** @var string|resource|false $card */
		$card = $result->fetchOne();
		$result->closeCursor();

		if ($card !== false) {
			return $this->readCardData($card);
		}

		return $this->findVisibleInSystemAddressBook($user, $uid, $email, $cloudId);
	}

	private function findVisibleInSystemAddressBook(IUser $user,
		?string $uid,
		?string $email,
		?string $cloudId): ?string {
		$result = $this->getMatchingCardsQuery('principals/system/system', 'system', $uid, $email, $cloudId)
			->executeQuery();
		try {
			/** @var string|resource|false $card */
			while (($card = $result->fetchOne()) !== false) {
				$cardData = $this->readCardData($card);
				$vCard = Reader::read($cardData);
				$uidProperty = $vCard->select('UID')[0] ?? null;
				$target = $uidProperty instanceof Property ? $this->userManager->get($uidProperty->getValue()) : null;
				if ($target !== null && $this->systemAddressBookVisibility->isUserCardVisible($user, $target)) {
					return $cardData;
				}
			}
		} finally {
			$result->closeCursor();
		}

		return null;
	}

	private function getMatchingCardsQuery(string $principalUri,
		?string $addressBookUri,
		?string $uid,
		?string $email,
		?string $cloudId): IQueryBuilder {
		$addressbooksQuery = $this->db->getQueryBuilder();
		$cardQuery = $this->db->getQueryBuilder();
		$propQuery = $this->db->getQueryBuilder();

		$additionalWheres = [];
		if ($uid !== null) {
			$additionalWheres[] = $propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('UID')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($uid))
			);
		}
		if ($email !== null) {
			$additionalWheres[] = $propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('EMAIL')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($email))
			);
		}
		if ($cloudId !== null) {
			$additionalWheres[] = $propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('CLOUD')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($cloudId))
			);
		}
		$addressbooksQuery->selectDistinct('id')
			->from('addressbooks')
			->where($addressbooksQuery->expr()->eq('principaluri', $cardQuery->createNamedParameter($principalUri)));
		if ($addressBookUri !== null) {
			$addressbooksQuery->andWhere($addressbooksQuery->expr()->eq('uri', $cardQuery->createNamedParameter($addressBookUri)));
		}
		$propQuery->selectDistinct('cardid')
			->from('cards_properties')
			->where($propQuery->expr()->in('addressbookid', $propQuery->createFunction($addressbooksQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->groupBy('cardid');

		if (!empty($additionalWheres)) {
			$propQuery->andWhere($propQuery->expr()->orX(...$additionalWheres));
		}

		return $cardQuery->select('carddata')
			->from('cards')
			->where($cardQuery->expr()->in('id', $cardQuery->createFunction($propQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($cardQuery->expr()->in('addressbookid', $cardQuery->createFunction($addressbooksQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY));
	}

	/**
	 * @param string|resource $card
	 */
	private function readCardData($card): string {
		if (is_resource($card)) {
			return stream_get_contents($card);
		}

		return $card;
	}
}
