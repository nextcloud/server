<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_Proxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;

class UuidUpdateReport {
	const UNCHANGED = 0;
	const UNKNOWN = 1;
	const UNREADABLE = 2;
	const UPDATED = 3;
	const UNWRITABLE = 4;
	const UNMAPPED = 5;

	public $id = '';
	public $dn = '';
	public $isUser = true;
	public $state = self::UNCHANGED;
	public $oldUuid = '';
	public $newUuid = '';

	public function __construct(string $id, string $dn, bool $isUser, int $state, string $oldUuid = '', string $newUuid = '') {
		$this->id = $id;
		$this->dn = $dn;
		$this->isUser = $isUser;
		$this->state = $state;
		$this->oldUuid = $oldUuid;
		$this->newUuid = $newUuid;
	}
}

class UpdateUUID extends Command {
	/** @var UserMapping */
	private $userMapping;
	/** @var GroupMapping */
	private $groupMapping;
	/** @var User_Proxy */
	private $userProxy;
	/** @var Group_Proxy */
	private $groupProxy;
	/** @var array<UuidUpdateReport[]> */
	protected $reports = [];
	/** @var LoggerInterface */
	private $logger;
	/** @var bool */
	private $dryRun = false;

	public function __construct(UserMapping $userMapping, GroupMapping $groupMapping, User_Proxy $userProxy, Group_Proxy $groupProxy, LoggerInterface $logger) {
		$this->userMapping = $userMapping;
		$this->groupMapping = $groupMapping;
		$this->userProxy = $userProxy;
		$this->groupProxy = $groupProxy;
		$this->logger = $logger;
		$this->reports = [
			UuidUpdateReport::UPDATED => [],
			UuidUpdateReport::UNKNOWN => [],
			UuidUpdateReport::UNREADABLE => [],
			UuidUpdateReport::UNWRITABLE => [],
			UuidUpdateReport::UNMAPPED => [],
		];
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:update-uuid')
			->setDescription('Attempts to update UUIDs of user and group entries. By default, the command attempts to update UUIDs that have been invalidated by a migration step.')
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'updates every user and group. All other options are ignored.'
			)
			->addOption(
				'userId',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'a user ID to update'
			)
			->addOption(
				'groupId',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'a group ID to update'
			)
			->addOption(
				'dn',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'a DN to update'
			)
			->addOption(
				'dry-run',
				null,
				InputOption::VALUE_NONE,
				'UUIDs will not be updated in the database'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->dryRun = $input->getOption('dry-run');
		$entriesToUpdate = $this->estimateNumberOfUpdates($input);
		$progress = new ProgressBar($output);
		$progress->start($entriesToUpdate);
		foreach($this->handleUpdates($input) as $_) {
			$progress->advance();
		}
		$progress->finish();
		$output->writeln('');
		$this->printReport($output);
		return count($this->reports[UuidUpdateReport::UNMAPPED]) === 0
			&& count($this->reports[UuidUpdateReport::UNREADABLE]) === 0
			&& count($this->reports[UuidUpdateReport::UNWRITABLE]) === 0
			? 0
			: 1;
	}

	protected function printReport(OutputInterface $output): void {
		if ($output->isQuiet()) {
			return;
		}

		if (count($this->reports[UuidUpdateReport::UPDATED]) === 0) {
			$output->writeln('<info>No record was updated.</info>');
		} else {
			$output->writeln(sprintf('<info>%d record(s) were updated.</info>', count($this->reports[UuidUpdateReport::UPDATED])));
			if ($output->isVerbose()) {
				/** @var UuidUpdateReport $report */
				foreach ($this->reports[UuidUpdateReport::UPDATED] as $report) {
					$output->writeln(sprintf('  %s had their old UUID %s updated to %s', $report->id, $report->oldUuid, $report->newUuid));
				}
				$output->writeln('');
			}
		}

		if (count($this->reports[UuidUpdateReport::UNMAPPED]) > 0) {
			$output->writeln(sprintf('<error>%d provided IDs were not mapped. These were:</error>', count($this->reports[UuidUpdateReport::UNMAPPED])));
			/** @var UuidUpdateReport $report */
			foreach ($this->reports[UuidUpdateReport::UNMAPPED] as $report) {
				if (!empty($report->id)) {
					$output->writeln(sprintf('  %s: %s',
						$report->isUser ? 'User' : 'Group', $report->id));
				} else if (!empty($report->dn)) {
					$output->writeln(sprintf('  DN: %s', $report->dn));
				}
			}
			$output->writeln('');
		}

		if (count($this->reports[UuidUpdateReport::UNKNOWN]) > 0) {
			$output->writeln(sprintf('<info>%d provided IDs were unknown on LDAP.</info>', count($this->reports[UuidUpdateReport::UNKNOWN])));
			if ($output->isVerbose()) {
				/** @var UuidUpdateReport $report */
				foreach ($this->reports[UuidUpdateReport::UNKNOWN] as $report) {
					$output->writeln(sprintf('  %s: %s',$report->isUser ? 'User' : 'Group', $report->id));
				}
				$output->writeln(PHP_EOL . 'Old users can be removed along with their data per occ user:delete.' . PHP_EOL);
			}
		}

		if (count($this->reports[UuidUpdateReport::UNREADABLE]) > 0) {
			$output->writeln(sprintf('<error>For %d records, the UUID could not be read. Double-check your configuration.</error>', count($this->reports[UuidUpdateReport::UNREADABLE])));
			if ($output->isVerbose()) {
				/** @var UuidUpdateReport $report */
				foreach ($this->reports[UuidUpdateReport::UNREADABLE] as $report) {
					$output->writeln(sprintf('  %s: %s',$report->isUser ? 'User' : 'Group', $report->id));
				}
			}
		}

		if (count($this->reports[UuidUpdateReport::UNWRITABLE]) > 0) {
			$output->writeln(sprintf('<error>For %d records, the UUID could not be saved to database. Double-check your configuration.</error>', count($this->reports[UuidUpdateReport::UNWRITABLE])));
			if ($output->isVerbose()) {
				/** @var UuidUpdateReport $report */
				foreach ($this->reports[UuidUpdateReport::UNWRITABLE] as $report) {
					$output->writeln(sprintf('  %s: %s',$report->isUser ? 'User' : 'Group', $report->id));
				}
			}
		}
	}

	protected function handleUpdates(InputInterface $input): \Generator {
		if ($input->getOption('all')) {
			foreach($this->handleMappingBasedUpdates(false) as $_) {
				yield;
			}
		} else if ($input->getOption('userId')
			|| $input->getOption('groupId')
			|| $input->getOption('dn')
		) {
			foreach($this->handleUpdatesByUserId($input->getOption('userId')) as $_) {
				yield;
			}
			foreach($this->handleUpdatesByGroupId($input->getOption('groupId')) as $_) {
				yield;
			}
			foreach($this->handleUpdatesByDN($input->getOption('dn')) as $_) {
				yield;
			}
		} else {
			foreach($this->handleMappingBasedUpdates(true) as $_) {
				yield;
			}
		}
	}

	protected function handleUpdatesByUserId(array $userIds): \Generator {
		foreach($this->handleUpdatesByEntryId($userIds, $this->userMapping) as $_) {
			yield;
		}
	}

	protected function handleUpdatesByGroupId(array $groupIds): \Generator {
		foreach($this->handleUpdatesByEntryId($groupIds, $this->groupMapping) as $_) {
			yield;
		}
	}

	protected function handleUpdatesByDN(array $dns): \Generator {
		$userList = $groupList = [];
		while ($dn = array_pop($dns)) {
			$uuid = $this->userMapping->getUUIDByDN($dn);
			if ($uuid) {
				$id = $this->userMapping->getNameByDN($dn);
				$userList[] = ['name' => $id, 'uuid' => $uuid];
				continue;
			}
			$uuid = $this->groupMapping->getUUIDByDN($dn);
			if ($uuid) {
				$id = $this->groupMapping->getNameByDN($dn);
				$groupList[] = ['name' => $id, 'uuid' => $uuid];
				continue;
			}
			$this->reports[UuidUpdateReport::UNMAPPED][] = new UuidUpdateReport('', $dn, true, UuidUpdateReport::UNMAPPED);
			yield;
		}
		foreach($this->handleUpdatesByList($this->userMapping, $userList) as $_) {
			yield;
		}
		foreach($this->handleUpdatesByList($this->groupMapping, $groupList) as $_) {
			yield;
		}
	}

	protected function handleUpdatesByEntryId(array $ids, AbstractMapping $mapping): \Generator {
		$isUser = $mapping instanceof UserMapping;
		$list = [];
		while ($id = array_pop($ids)) {
			if(!$dn = $mapping->getDNByName($id)) {
				$this->reports[UuidUpdateReport::UNMAPPED][] = new UuidUpdateReport($id, '', $isUser, UuidUpdateReport::UNMAPPED);
				yield;
				continue;
			}
			// Since we know it was mapped the UUID is populated
			$uuid = $mapping->getUUIDByDN($dn);
			$list[] = ['name' => $id, 'uuid' => $uuid];
		}
		foreach($this->handleUpdatesByList($mapping, $list) as $_) {
			yield;
		}
	}

	protected function handleMappingBasedUpdates(bool $invalidatedOnly): \Generator {
		$limit = 1000;
		/** @var AbstractMapping $mapping*/
		foreach([$this->userMapping, $this->groupMapping] as $mapping) {
			$offset = 0;
			do {
				$list = $mapping->getList($offset, $limit, $invalidatedOnly);
				$offset += $limit;

				foreach($this->handleUpdatesByList($mapping, $list) as $tick) {
					yield; // null, for it only advances progress counter
				}
			} while (count($list) === $limit);
		}
	}

	protected function handleUpdatesByList(AbstractMapping $mapping, array $list): \Generator {
		if ($mapping instanceof UserMapping) {
			$isUser = true;
			$backendProxy = $this->userProxy;
		} else {
			$isUser = false;
			$backendProxy = $this->groupProxy;
		}

		foreach ($list as $row) {
			$access = $backendProxy->getLDAPAccess($row['name']);
			if ($access instanceof Access
				&& $dn = $mapping->getDNByName($row['name']))
			{
				if ($uuid = $access->getUUID($dn, $isUser)) {
					if ($uuid !== $row['uuid']) {
						if ($this->dryRun || $mapping->setUUIDbyDN($uuid, $dn)) {
							$this->reports[UuidUpdateReport::UPDATED][]
								= new UuidUpdateReport($row['name'], $dn, $isUser, UuidUpdateReport::UPDATED, $row['uuid'], $uuid);
						} else {
							$this->reports[UuidUpdateReport::UNWRITABLE][]
								= new UuidUpdateReport($row['name'], $dn, $isUser, UuidUpdateReport::UNWRITABLE, $row['uuid'], $uuid);
						}
						$this->logger->info('UUID of {id} was updated from {from} to {to}',
							[
								'appid' => 'user_ldap',
								'id' => $row['name'],
								'from' => $row['uuid'],
								'to' => $uuid,
							]
						);
					}
				} else {
					$this->reports[UuidUpdateReport::UNREADABLE][] = new UuidUpdateReport($row['name'], $dn, $isUser, UuidUpdateReport::UNREADABLE);
				}
			} else {
				$this->reports[UuidUpdateReport::UNKNOWN][] = new UuidUpdateReport($row['name'], '', $isUser, UuidUpdateReport::UNKNOWN);
			}
			yield; // null, for it only advances progress counter
		}
	}

	protected function estimateNumberOfUpdates(InputInterface $input): int {
		if ($input->getOption('all')) {
			return $this->userMapping->count() + $this->groupMapping->count();
		} else if ($input->getOption('userId')
			|| $input->getOption('groupId')
			|| $input->getOption('dn')
		) {
			return count($input->getOption('userId'))
				+ count($input->getOption('groupId'))
				+ count($input->getOption('dn'));
		} else {
			return $this->userMapping->countInvalidated() + $this->groupMapping->countInvalidated();
		}
	}

}
