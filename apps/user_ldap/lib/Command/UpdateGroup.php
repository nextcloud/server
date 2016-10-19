<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \OCA\User_LDAP\Helper;
use \OCA\User_LDAP\LDAP;
use \OCA\User_LDAP\Group_Proxy;
use \OCA\User_LDAP\Mapping\GroupMapping;
use \OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class UpdateGroup extends Command {

	const ERROR_CODE_MISSING_CONF = 1;
	const ERROR_CODE_MISSING_MAPPING = 2;

	public function __construct(LDAP $ldap, Helper $helper, IDBConnection $connection) {
		$this->connection = $connection;
		$this->ldap = $ldap;
		$this->helper = $helper;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:update-group')
			->setDescription('update the specified group membership information stored locally')
			->addArgument(
					'groupID',
					InputArgument::REQUIRED | InputArgument::IS_ARRAY,
					'the group ID'
				)
			->addOption(
					'hide-warning',
					null,
					InputOption::VALUE_NONE,
					'Group membership attribute is critical for this command to work properly. This option hides the warning and the additional output associated with it.'
				);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$groupIDs = $input->getArgument('groupID');
		// make sure we don't have duplicated groups in the parameters
		$groupIDs = array_unique($groupIDs);

		$helper = $this->helper;
		$availableConfigs = $helper->getServerConfigurationPrefixes();

		if (!$input->getOption('hide-warning')) {
			$output->writeln("Group membership attribute is critical for this command, please verify.");
			// show configuration information, useful to debug
			foreach ($availableConfigs as $aconfig) {
				$config = new \OCA\User_LDAP\Configuration($aconfig);
				$message = '* ' . $config->ldapHost . ':' . $config->ldapPort . ' -> ' . $config->ldapGroupMemberAssocAttr;
				$output->writeln($message);
			}
		}


		if (empty($availableConfigs)) {
			$output->writeln('<error>No active configurations available</error>');
			return self::ERROR_CODE_MISSING_CONF;
		}

		$splittedGroups = $this->checkGroupMappingExists($groupIDs);
		if (!empty($splittedGroups['notinDB'])) {
			$missingGroups = implode(', ', $splittedGroups['notinDB']);
			$output->writeln("<error>The following groups are missing in the DB and will be skipped: $missingGroups</error>");
		}

		$groupProxy = new Group_Proxy($availableConfigs, $this->ldap);

		foreach ($splittedGroups['inDB'] as $groupID) {
			$output->writeln("checking group \"$groupID\"...");
			if (!$groupProxy->groupExists($groupID)) {
				$output->writeln("\"$groupID\" doesn't exist in LDAP any more, removing local mapping");
				$this->removeGroupMapping($groupID);
			} else {
				$output->writeln("updating \"$groupID\" group membership information locally", OutputInterface::VERBOSITY_VERBOSE);
				$userList = $groupProxy->usersInGroup($groupID);
				$userChanges = $this->updateGroupMapping($groupID, $userList);

				$output->writeln("triggering hooks", OutputInterface::VERBOSITY_VERBOSE);
				$output->writeln("new users:");
				foreach ($userChanges['added'] as $addedUser) {
					$output->writeln($addedUser);
					\OCP\Util::emitHook('OC_User', 'post_addToGroup', array('uid' => $addedUser, 'gid' => $groupID));
				}
				$output->writeln("removed users:");
				foreach ($userChanges['removed'] as $removedUser) {
					$output->writeln($removedUser);
					\OCP\Util::emitHook('OC_User', 'post_removeFromGroup', array('uid' => $removedUser, 'gid' => $groupID));
				}
			}
		}
	}


	private function removeGroupMapping($groupName) {
		$this->connection->beginTransaction();

		try {
			$query = $this->connection->getQueryBuilder();
			$query->delete('ldap_group_mapping')
				->where($query->expr()->eq('owncloud_name', $query->createParameter('group')))
				->setParameter('group', $groupName)
				->execute();

			$query2 = $this->connection->getQueryBuilder();
			$query2->delete('ldap_group_members')
				->where($query->expr()->eq('owncloudname', $query->createParameter('group')))
				->setParameter('group', $groupName)
				->execute();

			$this->connection->commit();
		} catch (\Exception $e) {
			// Rollback and rethrow the exception
			$this->connection->rollback();
			throw $e;
		}
	}

	/**
	 * Return and array with 2 lists: one for the users added and another for the users removed from
	 * the group:
	 * ['added' => ['user1', 'user20'], 'removed' => ['user22']]
	 *
	 * @param string $groupName name of the group to be checked
	 * @param array $userList array of user names to be compared. For example: ['user1', 'user44'].
	 * The list will usually come from the LDAP server and will be compared against the information
	 * in the DB.
	 */
	private function updateGroupMapping($groupName, $userList) {
		$query = $this->connection->getQueryBuilder();
		$needToInsert = false;
		$result = $query->select('*')
			->from('ldap_group_members')
			->where($query->expr()->eq('owncloudname', $query->createParameter('group')))
			->setParameter('group', $groupName)
			->execute();
		$row = $result->fetch();
		if ($row) {
			$needToInsert = false;
			$mappedList = unserialize($row['owncloudusers']);
		} else {
			$needToInsert = true;
		}
		if ($needToInsert) {
			$query2 = $this->connection->getQueryBuilder();
			$query2->insert('ldap_group_members')
				->setValue('owncloudname', $query2->createParameter('group'))
				->setValue('owncloudusers', $query2->createParameter('users'))
				->setParameter('group', $groupName)
				->setParameter('users', serialize($userList))
				->execute();
			return array('added' => $userList, 'removed' => array());
		} else {
			$query2 = $this->connection->getQueryBuilder();
			$query2->update('ldap_group_members')
				->set('owncloudusers', $query2->createParameter('users'))
				->where($query2->expr()->eq('owncloudname', $query2->createParameter('group')))
				->setParameter('group', $groupName)
				->setParameter('users', serialize($userList))
				->execute();
			// calculate changes
			$usersAdded = array_diff($userList, $mappedList);
			$usersRemoved = array_diff($mappedList, $userList);
			return array('added' => $usersAdded, 'removed' => $usersRemoved);
		}
	}

	/**
	 * Split the list of group names into those which are in the DB and those which aren't
	 * As an example, checkGroupMappingExists(['g1', 'g2', 'g3', 'missing', 'another']) could return:
	 * ['inDB' => ['g1', 'g2', 'g3'], 'notinDB' => ['missing', 'another']]
	 *
	 * @param array<String> $groupNames list of group names
	 * @return array returns an array containing 2 arrays: 'inDB' for those group names that are in
	 * the DB and 'notinDB' for those that aren't
	 */
	private function checkGroupMappingExists($groupNames) {
		$groups = array('inDB' => array(), 'notinDB' => array());
		$query = $this->connection->getQueryBuilder();
		$query->select('owncloud_name')
			->from('ldap_group_mapping')
			->where($query->expr()->in('owncloud_name', $query->createParameter('groups')))
			->setParameter('groups', $groupNames, IQueryBuilder::PARAM_STR_ARRAY);
		$result = $query->execute();

		// fill with those which are inside the DB
		while (($row = $result->fetch()) !== false) {
			if (in_array($row['owncloud_name'], $groupNames, true)) {
				$groups['inDB'][] = $row['owncloud_name'];
			}
		}

		$result->closeCursor();

		// fill with the missing ones
		$groups['notinDB'] = array_diff($groupNames, $groups['inDB']);
		return $groups;
	}
}
