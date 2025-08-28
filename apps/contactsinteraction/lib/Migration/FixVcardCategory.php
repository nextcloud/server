<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContactsInteraction\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Sabre\VObject\Reader;

class FixVcardCategory implements IRepairStep {

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Fix category of recent contacts vcards';
	}

	public function run(IOutput $output): void {
		$query = $this->connection->getQueryBuilder();

		$cardsWithTranslatedCategory = $query->select(['id', 'card'])
			->from('recent_contact')
			->where($query->expr()->notLike(
				'card',
				$query->createNamedParameter('%CATEGORIES:Recently contacted%')
			))
			->setMaxResults(5000)
			->executeQuery();

		$output->startProgress($cardsWithTranslatedCategory->rowCount());

		$this->connection->beginTransaction();

		$updateQuery = $query->update('recent_contact')
			->set('card', $query->createParameter('card'))
			->where($query->expr()->eq('id', $query->createParameter('id')));

		while ($card = $cardsWithTranslatedCategory->fetch()) {
			$vcard = Reader::read($card['card']);
			$vcard->remove('CATEGORIES');
			$vcard->add('CATEGORIES', 'Recently contacted');

			$updateQuery->setParameter('id', $card['id']);
			$updateQuery->setParameter('card', $vcard->serialize());
			$updateQuery->executeStatement();

			$output->advance(1);
		}

		$this->connection->commit();

		$output->finishProgress();

		$cardsWithTranslatedCategory->closeCursor();
	}
}
