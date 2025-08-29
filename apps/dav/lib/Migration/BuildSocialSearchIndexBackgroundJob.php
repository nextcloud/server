<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class BuildSocialSearchIndexBackgroundJob extends QueuedJob {
	public function __construct(
		private IDBConnection $db,
		private CardDavBackend $davBackend,
		private LoggerInterface $logger,
		private IJobList $jobList,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
	}

	public function run($argument) {
		$offset = $argument['offset'];
		$stopAt = $argument['stopAt'];

		$this->logger->info('Indexing social profile data (' . $offset . '/' . $stopAt . ')');

		$offset = $this->buildIndex($offset, $stopAt);

		if ($offset >= $stopAt) {
			$this->logger->info('All contacts with social profiles indexed');
		} else {
			$this->jobList->add(self::class, [
				'offset' => $offset,
				'stopAt' => $stopAt
			]);
			$this->logger->info('New social profile indexing job scheduled with offset ' . $offset);
		}
	}

	/**
	 * @param int $offset
	 * @param int $stopAt
	 * @return int
	 */
	private function buildIndex($offset, $stopAt) {
		$startTime = $this->time->getTime();

		// get contacts with social profiles
		$query = $this->db->getQueryBuilder();
		$query->select('id', 'addressbookid', 'uri', 'carddata')
			->from('cards', 'c')
			->orderBy('id', 'ASC')
			->where($query->expr()->like('carddata', $query->createNamedParameter('%SOCIALPROFILE%')))
			->andWhere($query->expr()->gt('id', $query->createNamedParameter((int)$offset, IQueryBuilder::PARAM_INT)))
			->setMaxResults(100);
		$social_cards = $query->executeQuery()->fetchAll();

		if (empty($social_cards)) {
			return $stopAt;
		}

		// refresh identified contacts in order to re-index
		foreach ($social_cards as $contact) {
			$offset = $contact['id'];
			$cardData = $contact['carddata'];
			if (is_resource($cardData) && (get_resource_type($cardData) === 'stream')) {
				$cardData = stream_get_contents($cardData);
			}
			$this->davBackend->updateCard($contact['addressbookid'], $contact['uri'], $cardData);

			// stop after 15sec (to be continued with next chunk)
			if (($this->time->getTime() - $startTime) > 15) {
				break;
			}
		}

		return $offset;
	}
}
