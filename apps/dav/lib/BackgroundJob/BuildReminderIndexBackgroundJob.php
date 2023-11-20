<?php

declare(strict_types=1);

/**
 * @copyright 2019 Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Class BuildReminderIndexBackgroundJob
 *
 * @package OCA\DAV\BackgroundJob
 */
class BuildReminderIndexBackgroundJob extends QueuedJob {

	/** @var IDBConnection */
	private $db;

	/** @var ReminderService */
	private $reminderService;

	private LoggerInterface $logger;

	/** @var IJobList */
	private $jobList;

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * BuildReminderIndexBackgroundJob constructor.
	 */
	public function __construct(IDBConnection $db,
								ReminderService $reminderService,
								LoggerInterface $logger,
								IJobList $jobList,
								ITimeFactory $timeFactory) {
		parent::__construct($timeFactory);
		$this->db = $db;
		$this->reminderService = $reminderService;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->timeFactory = $timeFactory;
	}

	public function run($argument) {
		$offset = (int) $argument['offset'];
		$stopAt = (int) $argument['stopAt'];

		$this->logger->info('Building calendar reminder index (' . $offset .'/' . $stopAt . ')');

		$offset = $this->buildIndex($offset, $stopAt);

		if ($offset >= $stopAt) {
			$this->logger->info('Building calendar reminder index done');
		} else {
			$this->jobList->add(self::class, [
				'offset' => $offset,
				'stopAt' => $stopAt
			]);
			$this->logger->info('Scheduled a new BuildReminderIndexBackgroundJob with offset ' . $offset);
		}
	}

	/**
	 * @param int $offset
	 * @param int $stopAt
	 * @return int
	 */
	private function buildIndex(int $offset, int $stopAt):int {
		$startTime = $this->timeFactory->getTime();

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('calendarobjects')
			->where($query->expr()->lte('id', $query->createNamedParameter($stopAt)))
			->andWhere($query->expr()->gt('id', $query->createNamedParameter($offset)))
			->orderBy('id', 'ASC');

		$result = $query->executeQuery();
		while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
			$offset = (int) $row['id'];
			if (is_resource($row['calendardata'])) {
				$row['calendardata'] = stream_get_contents($row['calendardata']);
			}
			$row['component'] = $row['componenttype'];

			try {
				$this->reminderService->onCalendarObjectCreate($row);
			} catch (\Exception $ex) {
				$this->logger->error($ex->getMessage(), ['exception' => $ex]);
			}

			if (($this->timeFactory->getTime() - $startTime) > 15) {
				$result->closeCursor();
				return $offset;
			}
		}

		$result->closeCursor();
		return $stopAt;
	}
}
