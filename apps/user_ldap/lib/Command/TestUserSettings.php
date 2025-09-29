<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestUserSettings extends Command {
	public function __construct(
		protected User_Proxy $backend,
		protected Group_Proxy $groupBackend,
		protected Helper $helper,
		protected DeletedUsersIndex $dui,
		protected UserMapping $mapping,
		protected GroupMapping $groupMapping,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:test-user-settings')
			->setDescription('Runs tests and show information about user related LDAP settings')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'the user name as used in Nextcloud, or the LDAP DN'
			)
			->addOption(
				'group',
				'g',
				InputOption::VALUE_REQUIRED,
				'A group DN to check if the user is a member or not'
			)
			->addOption(
				'clearcache',
				null,
				InputOption::VALUE_NONE,
				'Clear the cache of the LDAP connection before the beginning of tests'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$uid = $input->getArgument('user');
			$access = $this->backend->getLDAPAccess($uid);
			$connection = $access->getConnection();
			if ($input->getOption('clearcache')) {
				$connection->clearCache();
			}
			$configPrefix = $connection->getConfigPrefix();
			$knownDn = '';
			if ($access->stringResemblesDN($uid)) {
				$knownDn = $uid;
				$username = $access->dn2username($uid);
				if ($username !== false) {
					$uid = $username;
				}
			}

			$dn = $this->mapping->getDNByName($uid);
			if ($dn !== false) {
				$output->writeln("User <info>$dn</info> is mapped with account name <info>$uid</info>.");
				$uuid = $this->mapping->getUUIDByDN($dn);
				$output->writeln("Known UUID is <info>$uuid</info>.");
				if ($knownDn === '') {
					$knownDn = $dn;
				}
			} else {
				$output->writeln("User <info>$uid</info> is not mapped.");
			}

			if ($knownDn === '') {
				return self::SUCCESS;
			}

			if (!$access->isDNPartOfBase($knownDn, $access->getConnection()->ldapBaseUsers)) {
				$output->writeln(
					"User <info>$knownDn</info> is not in one of the configured user bases: <info>"
					. implode(',', $access->getConnection()->ldapBaseUsers)
					. '</info>.'
				);
			}

			$output->writeln("Configuration prefix is <info>$configPrefix</info>");
			$output->writeln('');

			$attributeNames = [
				'ldapBase',
				'ldapBaseUsers',
				'ldapExpertUsernameAttr',
				'ldapUuidUserAttribute',
				'ldapExpertUUIDUserAttr',
				'ldapQuotaAttribute',
				'ldapEmailAttribute',
				'ldapUserDisplayName',
				'ldapUserDisplayName2',
				'ldapExtStorageHomeAttribute',
				'ldapAttributePhone',
				'ldapAttributeWebsite',
				'ldapAttributeAddress',
				'ldapAttributeTwitter',
				'ldapAttributeFediverse',
				'ldapAttributeOrganisation',
				'ldapAttributeRole',
				'ldapAttributeHeadline',
				'ldapAttributeBiography',
				'ldapAttributeBirthDate',
				'ldapAttributePronouns',
				'ldapGidNumber',
				'hasGidNumber',
			];
			$output->writeln('Attributes set in configuration:');
			foreach ($attributeNames as $attributeName) {
				if (($connection->$attributeName !== '') && ($connection->$attributeName !== [])) {
					if (\is_string($connection->$attributeName)) {
						$output->writeln("- $attributeName: <info>" . $connection->$attributeName . '</info>');
					} else {
						$output->writeln("- $attributeName: <info>" . \json_encode($connection->$attributeName) . '</info>');
					}
				}
			}

			$filter = $connection->ldapUserFilter;
			$attrs = $access->userManager->getAttributes(true);
			$attrs[] = strtolower($connection->ldapExpertUsernameAttr);
			if ($connection->ldapUuidUserAttribute !== 'auto') {
				$attrs[] = strtolower($connection->ldapUuidUserAttribute);
			}
			if ($connection->hasGidNumber) {
				$attrs[] = strtolower($connection->ldapGidNumber);
			}
			$attrs[] = 'memberof';
			$attrs = array_values(array_unique($attrs));
			$attributes = $access->readAttributes($knownDn, $attrs, $filter);

			if ($attributes === false) {
				$output->writeln(
					"LDAP read on <info>$knownDn</info> with filter <info>$filter</info> failed."
				);
				return self::FAILURE;
			}

			$output->writeln("Attributes fetched from LDAP using filter <info>$filter</info>:");
			foreach ($attributes as $attribute => $value) {
				$output->writeln(
					"- $attribute: <info>" . json_encode($value) . '</info>'
				);
			}

			$uuid = $access->getUUID($knownDn);
			if ($connection->ldapUuidUserAttribute === 'auto') {
				$output->writeln('<error>Failed to detect UUID attribute</error>');
			} else {
				$output->writeln('Detected UUID attribute: <info>' . $connection->ldapUuidUserAttribute . '</info>');
			}
			if ($uuid === false) {
				$output->writeln("<error>Failed to find UUID for $knownDn</error>");
			} else {
				$output->writeln("UUID for <info>$knownDn</info>: <info>$uuid</info>");
			}

			$groupLdapInstance = $this->groupBackend->getBackend($configPrefix);

			$output->writeln('');
			$output->writeln('Group information:');

			$attributeNames = [
				'ldapBaseGroups',
				'ldapDynamicGroupMemberURL',
				'ldapGroupFilter',
				'ldapGroupMemberAssocAttr',
			];
			$output->writeln('Configuration:');
			foreach ($attributeNames as $attributeName) {
				if ($connection->$attributeName !== '') {
					$output->writeln("- $attributeName: <info>" . $connection->$attributeName . '</info>');
				}
			}

			$primaryGroup = $groupLdapInstance->getUserPrimaryGroup($knownDn);
			$output->writeln('Primary group: <info>' . ($primaryGroup !== false? $primaryGroup:'') . '</info>');

			$groupByGid = $groupLdapInstance->getUserGroupByGid($knownDn);
			$output->writeln('Group from gidNumber: <info>' . ($groupByGid !== false? $groupByGid:'') . '</info>');

			$groups = $groupLdapInstance->getUserGroups($uid);
			$output->writeln('All known groups: <info>' . json_encode($groups) . '</info>');

			$memberOfUsed = ((int)$access->connection->hasMemberOfFilterSupport === 1
				&& (int)$access->connection->useMemberOfToDetectMembership === 1);

			$output->writeln('MemberOf usage: <info>' . ($memberOfUsed ? 'on' : 'off') . '</info> (' . $access->connection->hasMemberOfFilterSupport . ',' . $access->connection->useMemberOfToDetectMembership . ')');

			$gid = (string)$input->getOption('group');
			if ($gid === '') {
				return self::SUCCESS;
			}

			$output->writeln('');
			$output->writeln("Group $gid:");
			$knownGroupDn = '';
			if ($access->stringResemblesDN($gid)) {
				$knownGroupDn = $gid;
				$groupname = $access->dn2groupname($gid);
				if ($groupname !== false) {
					$gid = $groupname;
				}
			}

			$groupDn = $this->groupMapping->getDNByName($gid);
			if ($groupDn !== false) {
				$output->writeln("Group <info>$groupDn</info> is mapped with name <info>$gid</info>.");
				$groupUuid = $this->groupMapping->getUUIDByDN($groupDn);
				$output->writeln("Known UUID is <info>$groupUuid</info>.");
				if ($knownGroupDn === '') {
					$knownGroupDn = $groupDn;
				}
			} else {
				$output->writeln("Group <info>$gid</info> is not mapped.");
			}

			$members = $groupLdapInstance->usersInGroup($gid);
			$output->writeln('Members: <info>' . json_encode($members) . '</info>');

			return self::SUCCESS;

		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}
	}
}
