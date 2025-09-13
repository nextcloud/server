<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContactsInteraction\Migration;

use OC\Migration\BackgroundRepair;
use OCA\ContactsInteraction\AppInfo\Application;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

class FixVcardCategory implements IRepairStep {

	private const CARDS_PER_BATCH = 5000;

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly IJobList $jobList,
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
			->setMaxResults(self::CARDS_PER_BATCH)
			->executeQuery();
		$rowCount = $cardsWithTranslatedCategory->rowCount();

		$output->startProgress($rowCount);

		$this->connection->beginTransaction();

		$updateQuery = $query->update('recent_contact')
			->set('card', $query->createParameter('card'))
			->where($query->expr()->eq('id', $query->createParameter('id')));

		while ($card = $cardsWithTranslatedCategory->fetch()) {
			$output->advance(1);

			try {
				$vcard = Reader::read($card['card']);
			} catch (ParseException $e) {
				$output->info('Could not parse vcard with id ' . $card['id']);
				continue;
			}

			$vcard->remove('CATEGORIES');
			$vcard->add('CATEGORIES', 'Recently contacted');

			$updateQuery->setParameter('id', $card['id']);
			$updateQuery->setParameter('card', $vcard->serialize());
			$updateQuery->executeStatement();
		}

		$this->connection->commit();

		$cardsWithTranslatedCategory->closeCursor();

		$output->finishProgress();

		if ($rowCount === self::CARDS_PER_BATCH) {
			$this->jobList->add(BackgroundRepair::class, [
				'app' => Application::APP_ID,
				'step' => FixVcardCategory::class,
				'reschedule' => time(), // Use a different argument to reschedule the job
			]);
		}
	}
}
