<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Service\UpdateGroupsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckGroup extends Command {
	public function __construct(
		private UpdateGroupsService $service,
		protected Group_Proxy $backend,
		protected Helper $helper,
		protected GroupMapping $mapping,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:check-group')
			->setDescription('checks whether a group exists on LDAP.')
			->addArgument(
				'ocName',
				InputArgument::REQUIRED,
				'the group name as used in Nextcloud, or the LDAP DN'
			)
			->addOption(
				'force',
				null,
				InputOption::VALUE_NONE,
				'ignores disabled LDAP configuration'
			)
			->addOption(
				'update',
				null,
				InputOption::VALUE_NONE,
				'syncs values from LDAP'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->assertAllowed($input->getOption('force'));
			$gid = $input->getArgument('ocName');
			if ($this->backend->getLDAPAccess($gid)->stringResemblesDN($gid)) {
				$groupname = $this->backend->dn2GroupName($gid);
				if ($groupname !== false) {
					$gid = $groupname;
				}
			}
			$wasMapped = $this->groupWasMapped($gid);
			$exists = $this->backend->groupExistsOnLDAP($gid, true);
			if ($exists === true) {
				$output->writeln('The group is still available on LDAP.');
				if ($input->getOption('update')) {
					$this->updateGroup($gid, $output, $wasMapped);
				}
				return 0;
			} elseif ($wasMapped) {
				$output->writeln('The group does not exists on LDAP anymore.');
				return 0;
			} else {
				throw new \Exception('The given group is not a recognized LDAP group.');
			}
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage(). '</error>');
			return 1;
		}
	}

	/**
	 * checks whether a group is actually mapped
	 * @param string $ocName the groupname as used in Nextcloud
	 */
	protected function groupWasMapped(string $ocName): bool {
		$dn = $this->mapping->getDNByName($ocName);
		return $dn !== false;
	}

	/**
	 * checks whether the setup allows reliable checking of LDAP group existence
	 * @throws \Exception
	 */
	protected function assertAllowed(bool $force): void {
		if ($this->helper->haveDisabledConfigurations() && !$force) {
			throw new \Exception('Cannot check group existence, because '
				. 'disabled LDAP configurations are present.');
		}

		// we don't check ldapUserCleanupInterval from config.php because this
		// action is triggered manually, while the setting only controls the
		// background job.
	}

	private function updateGroup(string $gid, OutputInterface $output, bool $wasMapped): void {
		try {
			if ($wasMapped) {
				$this->service->handleKnownGroups([$gid]);
			} else {
				$this->service->handleCreatedGroups([$gid]);
			}
		} catch (\Exception $e) {
			$output->writeln('<error>Error while trying to lookup and update attributes from LDAP</error>');
		}
	}
}
