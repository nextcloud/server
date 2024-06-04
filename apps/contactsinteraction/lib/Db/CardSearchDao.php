<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use function is_resource;
use function stream_get_contents;

class CardSearchDao {
	private IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function findExisting(IUser $user,
		?string $uid,
		?string $email,
		?string $cloudId): ?string {
		$addressbooksQuery = $this->db->getQueryBuilder();
		$cardQuery = $this->db->getQueryBuilder();
		$propQuery = $this->db->getQueryBuilder();

		$propOr = $propQuery->expr()->orX();
		if ($uid !== null) {
			$propOr->add($propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('UID')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($uid))
			));
		}
		if ($email !== null) {
			$propOr->add($propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('EMAIL')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($email))
			));
		}
		if ($cloudId !== null) {
			$propOr->add($propQuery->expr()->andX(
				$propQuery->expr()->eq('name', $cardQuery->createNamedParameter('CLOUD')),
				$propQuery->expr()->eq('value', $cardQuery->createNamedParameter($cloudId))
			));
		}
		$addressbooksQuery->selectDistinct('id')
			->from('addressbooks')
			->where($addressbooksQuery->expr()->eq('principaluri', $cardQuery->createNamedParameter("principals/users/" . $user->getUID())));
		$propQuery->selectDistinct('cardid')
			->from('cards_properties')
			->where($propQuery->expr()->in('addressbookid', $propQuery->createFunction($addressbooksQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($propOr)
			->groupBy('cardid');
		$cardQuery->select('carddata')
			->from('cards')
			->where($cardQuery->expr()->in('id', $cardQuery->createFunction($propQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($cardQuery->expr()->in('addressbookid', $cardQuery->createFunction($addressbooksQuery->getSQL()), IQueryBuilder::PARAM_INT_ARRAY))
			->setMaxResults(1);
		$result = $cardQuery->execute();
		/** @var string|resource|false $card */
		$card = $result->fetchOne();

		if ($card === false) {
			return null;
		}
		if (is_resource($card)) {
			return stream_get_contents($card);
		}

		return $card;
	}
}
