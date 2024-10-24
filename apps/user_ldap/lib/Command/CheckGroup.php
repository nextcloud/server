<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Service\UpdateGroupsService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
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
		protected IEventDispatcher $dispatcher,
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
		$this->dispatcher->addListener(GroupCreatedEvent::class, fn ($event) => $this->onGroupCreatedEvent($event, $output));
		$this->dispatcher->addListener(UserAddedEvent::class, fn ($event) => $this->onUserAddedEvent($event, $output));
		$this->dispatcher->addListener(UserRemovedEvent::class, fn ($event) => $this->onUserRemovedEvent($event, $output));
		try {
			$this->assertAllowed($input->getOption('force'));
			$gid = $input->getArgument('ocName');
			$wasMapped = $this->groupWasMapped($gid);
			if ($this->backend->getLDAPAccess($gid)->stringResemblesDN($gid)) {
				$groupname = $this->backend->dn2GroupName($gid);
				if ($groupname !== false) {
					$gid = $groupname;
				}
			}
			/* Search to trigger mapping for new groups */
			$this->backend->getGroups($gid);
			$exists = $this->backend->groupExistsOnLDAP($gid, true);
			if ($exists === true) {
				$output->writeln('The group is still available on LDAP.');
				if ($input->getOption('update')) {
					$this->backend->getLDAPAccess($gid)->connection->clearCache();
					if ($wasMapped) {
						$this->service->handleKnownGroups([$gid]);
					} else {
						$this->service->handleCreatedGroups([$gid]);
					}
				}
				return self::SUCCESS;
			}

			if ($wasMapped) {
				$output->writeln('The group does not exist on LDAP anymore.');
				if ($input->getOption('update')) {
					$this->backend->getLDAPAccess($gid)->connection->clearCache();
					$this->service->handleRemovedGroups([$gid]);
				}
				return self::SUCCESS;
			}

			throw new \Exception('The given group is not a recognized LDAP group.');
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
	}

	public function onGroupCreatedEvent(GroupCreatedEvent $event, OutputInterface $output): void {
		$output->writeln('<info>The group ' . $event->getGroup()->getGID() . ' was added to Nextcloud with ' . $event->getGroup()->count() . ' users</info>');
	}

	public function onUserAddedEvent(UserAddedEvent $event, OutputInterface $output): void {
		$user = $event->getUser();
		$group = $event->getGroup();
		$output->writeln('<info>The user ' . $user->getUID() . ' was added to group ' . $group->getGID() . '</info>');
	}

	public function onUserRemovedEvent(UserRemovedEvent $event, OutputInterface $output): void {
		$user = $event->getUser();
		$group = $event->getGroup();
		$output->writeln('<info>The user ' . $user->getUID() . ' was removed from group ' . $group->getGID() . '</info>');
	}

	/**
	 * checks whether a group is actually mapped
	 * @param string $gid the groupname as passed to the command
	 */
	protected function groupWasMapped(string $gid): bool {
		$dn = $this->mapping->getDNByName($gid);
		if ($dn !== false) {
			return true;
		}
		$name = $this->mapping->getNameByDN($gid);
		return $name !== false;
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
