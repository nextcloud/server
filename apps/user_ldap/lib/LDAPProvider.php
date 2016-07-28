<?php
/**
 *
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP;

use OCP\IUserBackend;
use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\IDeletionFlagSupport;
use OCP\IServerContainer;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\Mapping\UserMapping;

/**
 * LDAP provider for pulic access to the LDAP backend.
 */
class LDAPProvider implements ILDAPProvider, IDeletionFlagSupport {

	private $backend;
	private $logger;
	private $helper;
	private $deletedUsersIndex;
	
	/**
	 * Create new LDAPProvider
	 * @param \OCP\IServerContainer $serverContainer
	 * @throws \Exception if user_ldap app was not enabled
	 */
	public function __construct(IServerContainer $serverContainer, Helper $helper, DeletedUsersIndex $deletedUsersIndex) {
		$this->logger = $serverContainer->getLogger();
		$this->helper = $helper;
		$this->deletedUsersIndex = $deletedUsersIndex;
		foreach ($serverContainer->getUserManager()->getBackends() as $backend){
			$this->logger->debug('instance '.get_class($backend).' backend.', ['app' => 'user_ldap']);
			if ($backend instanceof IUserLDAP) {
				$this->backend = $backend;
				return;
			}
        }
		throw new \Exception('To use the LDAPProvider, user_ldap app must be enabled');
	}
	
	/**
	 * Translate an user id to LDAP DN
	 * @param string $uid user id
	 * @return string with the LDAP DN
	 * @throws \Exception if translation was unsuccessful
	 */
	public function getUserDN($uid) {
		if(!$this->backend->userExists($uid)){
			throw new \Exception('User id not found in LDAP');
		}
		$result = $this->backend->getLDAPAccess($uid)->username2dn($uid);
		if(!$result){
			throw new \Exception('Translation to LDAP DN unsuccessful');
		}
		return $result;
	}
	
	/**
	 * Translate a LDAP DN to an internal user name. If there is no mapping between 
	 * the DN and the user name, a new one will be created.
	 * @param string $dn LDAP DN
	 * @return string with the internal user name
	 * @throws \Exception if translation was unsuccessful
	 */
	public function getUserName($dn) {
		$result = $this->backend->dn2UserName($dn);
		if(!$result){
			throw new \Exception('Translation to internal user name unsuccessful');
		}
		return $result;
	}
	
	/**
	 * Convert a stored DN so it can be used as base parameter for LDAP queries.
	 * @param string $dn the DN in question
	 * @return string
	 */
	public function DNasBaseParameter($dn) {
		return $this->helper->DNasBaseParameter($dn);
	}
	
	/**
	 * Sanitize a DN received from the LDAP server.
	 * @param array $dn the DN in question
	 * @return array the sanitized DN
	 */
	public function sanitizeDN($dn) {
		return $this->helper->sanitizeDN($dn);
	}
	
	/**
	 * Return a new LDAP connection resource for the specified user. 
	 * The connection must be closed manually.
	 * @param string $uid user id
	 * @return resource of the LDAP connection
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPConnection($uid) {
		if(!$this->backend->userExists($uid)){
			throw new \Exception('User id not found in LDAP');
		}
		return $this->backend->getNewLDAPConnection($uid);
	}
	
	/**
	 * Get the LDAP base for users.
	 * @param string $uid user id
	 * @return string the base for users
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPBaseUsers($uid) {
		if(!$this->backend->userExists($uid)){
			throw new \Exception('User id not found in LDAP');
		}	
		return $this->backend->getLDAPAccess($uid)->getConnection()->getConfiguration()['ldap_base_users'];
	}
	
	/**
	 * Get the LDAP base for groups.
	 * @param string $uid user id
	 * @return string the base for groups
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function getLDAPBaseGroups($uid) {
		if(!$this->backend->userExists($uid)){
			throw new \Exception('User id not found in LDAP');
		}
		return $this->backend->getLDAPAccess($uid)->getConnection()->getConfiguration()['ldap_base_groups'];
	}
	
	/**
	 * Clear the cache if a cache is used, otherwise do nothing.
	 * @param string $uid user id
	 * @throws \Exception if user id was not found in LDAP
	 */
	public function clearCache($uid) {
		if(!$this->backend->userExists($uid)){
			throw new \Exception('User id not found in LDAP');
		}
		$this->backend->getLDAPAccess($uid)->getConnection()->clearCache();
	}
	
	/**
	 * Check whether a LDAP DN exists
	 * @param string $dn LDAP DN
	 * @return bool whether the DN exists
	 */
	public function dnExists($dn) {
		$result = $this->backend->dn2UserName($dn);
		return !$result ? false : true;
	}
	
	/**
	 * Flag record for deletion.
	 * @param string $uid user id
	 */
	public function flagRecord($uid) {
		$this->deletedUsersIndex->markUser($uid);
	}
	
	/**
	 * Unflag record for deletion.
	 * @param string $uid user id
	 */
	public function unflagRecord($uid) {
		//do nothing
	}
}
