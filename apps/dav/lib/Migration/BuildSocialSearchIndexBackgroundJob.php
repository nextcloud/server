<?php

declare(strict_types=1);

/**
 * @copyright 2020 Matthias Heinisch <nextcloud@matthiasheinisch.de>
 *
 * @author call-me-matt <nextcloud@matthiasheinisch.de>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
namespace OCA\DAV\Migration;

use OCA\DAV\CardDAV\CardDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
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

	public function run($arguments) {
		$offset = $arguments['offset'];
		$stopAt = $arguments['stopAt'];

		$this->logger->info('Indexing social profile data (' . $offset .'/' . $stopAt . ')');

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
			->setMaxResults(100);
		$social_cards = $query->executeQuery()->fetchAll();

		if (empty($social_cards)) {
			return $stopAt;
		}

		// refresh identified contacts in order to re-index
		foreach ($social_cards as $contact) {
			$offset = $contact['id'];
			$this->davBackend->updateCard($contact['addressbookid'], $contact['uri'], $contact['carddata']);

			// stop after 15sec (to be continued with next chunk)
			if (($this->time->getTime() - $startTime) > 15) {
				break;
			}
		}

		return $offset;
	}
}
