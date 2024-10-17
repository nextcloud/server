<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Sabre\VObject\InvalidDataException;

class CalDAVRemoveEmptyValue implements IRepairStep {

	public function __construct(
		private IDBConnection $db,
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
	) {
	}

	public function getName() {
		return 'Fix broken values of calendar objects';
	}

	public function run(IOutput $output) {
		$pattern = ';VALUE=:';
		$count = $warnings = 0;

		$objects = $this->getInvalidObjects($pattern);

		$output->startProgress(count($objects));
		foreach ($objects as $row) {
			$calObject = $this->calDavBackend->getCalendarObject((int)$row['calendarid'], $row['uri']);
			$data = preg_replace('/' . $pattern . '/', ':', $calObject['calendardata']);

			if ($data !== $calObject['calendardata']) {
				$output->advance();

				try {
					$this->calDavBackend->getDenormalizedData($data);
				} catch (InvalidDataException $e) {
					$this->logger->info('Calendar object for calendar {cal} with uri {uri} still invalid', [
						'app' => 'dav',
						'cal' => (int)$row['calendarid'],
						'uri' => $row['uri'],
					]);
					$warnings++;
					continue;
				}

				$this->calDavBackend->updateCalendarObject((int)$row['calendarid'], $row['uri'], $data);
				$count++;
			}
		}
		$output->finishProgress();

		if ($warnings > 0) {
			$output->warning(sprintf('%d events could not be updated, see log file for more information', $warnings));
		}
		if ($count > 0) {
			$output->info(sprintf('Updated %d events', $count));
		}
	}

	protected function getInvalidObjects($pattern) {
		if ($this->db->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
			$rows = [];
			$chunkSize = 500;
			$query = $this->db->getQueryBuilder();
			$query->select($query->func()->count('*', 'num_entries'))
				->from('calendarobjects');
			$result = $query->executeQuery();
			$count = $result->fetchOne();
			$result->closeCursor();

			$numChunks = ceil($count / $chunkSize);

			$query = $this->db->getQueryBuilder();
			$query->select(['calendarid', 'uri', 'calendardata'])
				->from('calendarobjects')
				->setMaxResults($chunkSize);
			for ($chunk = 0; $chunk < $numChunks; $chunk++) {
				$query->setFirstResult($chunk * $chunkSize);
				$result = $query->executeQuery();

				while ($row = $result->fetch()) {
					if (mb_strpos($row['calendardata'], $pattern) !== false) {
						unset($row['calendardata']);
						$rows[] = $row;
					}
				}
				$result->closeCursor();
			}
			return $rows;
		}

		$query = $this->db->getQueryBuilder();
		$query->select(['calendarid', 'uri'])
			->from('calendarobjects')
			->where($query->expr()->like(
				'calendardata',
				$query->createNamedParameter(
					'%' . $this->db->escapeLikeParameter($pattern) . '%',
					IQueryBuilder::PARAM_STR
				),
				IQueryBuilder::PARAM_STR
			));

		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return $rows;
	}
}
