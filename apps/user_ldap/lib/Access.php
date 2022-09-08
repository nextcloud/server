<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Aaron Wood <aaronjwood@gmail.com>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Benjamin Diele <benjamin@diele.be>
 * @author bline <scottbeck@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Lorenzo M. Catucci <lorenzo@sancho.ccd.uniroma2.it>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Mario Kolling <mario.kolling@serpro.gov.br>
 * @author Max Kovalenko <mxss1998@yandex.ru>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nicolas Grekas <nicolas.grekas@gmail.com>
 * @author Peter Kubica <peter@kubica.ch>
 * @author Ralph Krimmel <rkrimme1@gwdg.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Roland Tapken <roland@bitarbeiter.net>
 * @author root <root@localhost.localdomain>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
namespace OCA\User_LDAP;

use DomainException;
use OC\Hooks\PublicEmitter;
use OC\ServerNotAvailableException;
use OCA\User_LDAP\Exceptions\ConstraintViolationException;
use OCA\User_LDAP\Exceptions\NoMoreResults;
use OCA\User_LDAP\Mapping\AbstractMapping;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\OfflineUser;
use OCP\HintException;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use function strlen;
use function substr;

/**
 * Class Access
 *
 * @package OCA\User_LDAP
 */
class Access extends LDAPUtility {
	public const UUID_ATTRIBUTES = ['entryuuid', 'nsuniqueid', 'objectguid', 'guid', 'ipauniqueid'];

	/** @var \OCA\User_LDAP\Connection */
	public $connection;
	/** @var Manager */
	public $userManager;
	/**
	 * never ever check this var directly, always use getPagedSearchResultState
	 * @var ?bool
	 */
	protected $pagedSearchedSuccessful;

	/** @var ?AbstractMapping */
	protected $userMapper;

	/** @var ?AbstractMapping */
	protected $groupMapper;

	/**
	 * @var \OCA\User_LDAP\Helper
	 */
	private $helper;
	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $ncUserManager;
	/** @var LoggerInterface */
	private $logger;
	private string $lastCookie = '';

	public function __construct(
		Connection $connection,
		ILDAPWrapper $ldap,
		Manager $userManager,
		Helper $helper,
		IConfig $config,
		IUserManager $ncUserManager,
		LoggerInterface $logger
	) {
		parent::__construct($ldap);
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->userManager->setLdapAccess($this);
		$this->helper = $helper;
		$this->config = $config;
		$this->ncUserManager = $ncUserManager;
		$this->logger = $logger;
	}

	/**
	 * sets the User Mapper
	 */
	public function setUserMapper(AbstractMapping $mapper): void {
		$this->userMapper = $mapper;
	}

	/**
	 * @throws \Exception
	 */
	public function getUserMapper(): AbstractMapping {
		if (is_null($this->userMapper)) {
			throw new \Exception('UserMapper was not assigned to this Access instance.');
		}
		return $this->userMapper;
	}

	/**
	 * sets the Group Mapper
	 */
	public function setGroupMapper(AbstractMapping $mapper): void {
		$this->groupMapper = $mapper;
	}

	/**
	 * returns the Group Mapper
	 *
	 * @throws \Exception
	 */
	public function getGroupMapper(): AbstractMapping {
		if (is_null($this->groupMapper)) {
			throw new \Exception('GroupMapper was not assigned to this Access instance.');
		}
		return $this->groupMapper;
	}

	/**
	 * @return bool
	 */
	private function checkConnection() {
		return ($this->connection instanceof Connection);
	}

	/**
	 * returns the Connection instance
	 *
	 * @return \OCA\User_LDAP\Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * reads a given attribute for an LDAP record identified by a DN
	 *
	 * @param string $dn the record in question
	 * @param string $attr the attribute that shall be retrieved
	 *        if empty, just check the record's existence
	 * @param string $filter
	 * @return array|false an array of values on success or an empty
	 *          array if $attr is empty, false otherwise
	 * @throws ServerNotAvailableException
	 */
	public function readAttribute(string $dn, string $attr, string $filter = 'objectClass=*') {
		if (!$this->checkConnection()) {
			$this->logger->warning(
				'No LDAP Connector assigned, access impossible for readAttribute.',
				['app' => 'user_ldap']
			);
			return false;
		}
		$cr = $this->connection->getConnectionResource();
		if (!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'user_ldap']);
			return false;
		}
		$attr = mb_strtolower($attr, 'UTF-8');
		// the actual read attribute later may contain parameters on a ranged
		// request, e.g. member;range=99-199. Depends on server reply.
		$attrToRead = $attr;

		$values = [];
		$isRangeRequest = false;
		do {
			$result = $this->executeRead($dn, $attrToRead, $filter);
			if (is_bool($result)) {
				// when an exists request was run and it was successful, an empty
				// array must be returned
				return $result ? [] : false;
			}

			if (!$isRangeRequest) {
				$values = $this->extractAttributeValuesFromResult($result, $attr);
				if (!empty($values)) {
					return $values;
				}
			}

			$isRangeRequest = false;
			$result = $this->extractRangeData($result, $attr);
			if (!empty($result)) {
				$normalizedResult = $this->extractAttributeValuesFromResult(
					[$attr => $result['values']],
					$attr
				);
				$values = array_merge($values, $normalizedResult);

				if ($result['rangeHigh'] === '*') {
					// when server replies with * as high range value, there are
					// no more results left
					return $values;
				} else {
					$low = $result['rangeHigh'] + 1;
					$attrToRead = $result['attributeName'] . ';range=' . $low . '-*';
					$isRangeRequest = true;
				}
			}
		} while ($isRangeRequest);

		$this->logger->debug('Requested attribute ' . $attr . ' not found for ' . $dn, ['app' => 'user_ldap']);
		return false;
	}

	/**
	 * Runs an read operation against LDAP
	 *
	 * @return array|bool false if there was any error, true if an exists check
	 *                    was performed and the requested DN found, array with the
	 *                    returned data on a successful usual operation
	 * @throws ServerNotAvailableException
	 */
	public function executeRead(string $dn, string $attribute, string $filter) {
		$dn = $this->helper->DNasBaseParameter($dn);
		$rr = @$this->invokeLDAPMethod('read', $dn, $filter, [$attribute]);
		if (!$this->ldap->isResource($rr)) {
			if ($attribute !== '') {
				//do not throw this message on userExists check, irritates
				$this->logger->debug('readAttribute failed for DN ' . $dn, ['app' => 'user_ldap']);
			}
			//in case an error occurs , e.g. object does not exist
			return false;
		}
		if ($attribute === '' && ($filter === 'objectclass=*' || $this->invokeLDAPMethod('countEntries', $rr) === 1)) {
			$this->logger->debug('readAttribute: ' . $dn . ' found', ['app' => 'user_ldap']);
			return true;
		}
		$er = $this->invokeLDAPMethod('firstEntry', $rr);
		if (!$this->ldap->isResource($er)) {
			//did not match the filter, return false
			return false;
		}
		//LDAP attributes are not case sensitive
		$result = \OCP\Util::mb_array_change_key_case(
			$this->invokeLDAPMethod('getAttributes', $er), MB_CASE_LOWER, 'UTF-8');

		return $result;
	}

	/**
	 * Normalizes a result grom getAttributes(), i.e. handles DNs and binary
	 * data if present.
	 *
	 * @param array $result from ILDAPWrapper::getAttributes()
	 * @param string $attribute the attribute name that was read
	 * @return string[]
	 */
	public function extractAttributeValuesFromResult($result, $attribute) {
		$values = [];
		if (isset($result[$attribute]) && $result[$attribute]['count'] > 0) {
			$lowercaseAttribute = strtolower($attribute);
			for ($i = 0; $i < $result[$attribute]['count']; $i++) {
				if ($this->resemblesDN($attribute)) {
					$values[] = $this->helper->sanitizeDN($result[$attribute][$i]);
				} elseif ($lowercaseAttribute === 'objectguid' || $lowercaseAttribute === 'guid') {
					$values[] = $this->convertObjectGUID2Str($result[$attribute][$i]);
				} else {
					$values[] = $result[$attribute][$i];
				}
			}
		}
		return $values;
	}

	/**
	 * Attempts to find ranged data in a getAttribute results and extracts the
	 * returned values as well as information on the range and full attribute
	 * name for further processing.
	 *
	 * @param array $result from ILDAPWrapper::getAttributes()
	 * @param string $attribute the attribute name that was read. Without ";range=…"
	 * @return array If a range was detected with keys 'values', 'attributeName',
	 *               'attributeFull' and 'rangeHigh', otherwise empty.
	 */
	public function extractRangeData($result, $attribute) {
		$keys = array_keys($result);
		foreach ($keys as $key) {
			if ($key !== $attribute && strpos((string)$key, $attribute) === 0) {
				$queryData = explode(';', (string)$key);
				if (strpos($queryData[1], 'range=') === 0) {
					$high = substr($queryData[1], 1 + strpos($queryData[1], '-'));
					$data = [
						'values' => $result[$key],
						'attributeName' => $queryData[0],
						'attributeFull' => $key,
						'rangeHigh' => $high,
					];
					return $data;
				}
			}
		}
		return [];
	}

	/**
	 * Set password for an LDAP user identified by a DN
	 *
	 * @param string $userDN the user in question
	 * @param string $password the new password
	 * @return bool
	 * @throws HintException
	 * @throws \Exception
	 */
	public function setPassword($userDN, $password) {
		if ((int)$this->connection->turnOnPasswordChange !== 1) {
			throw new \Exception('LDAP password changes are disabled.');
		}
		$cr = $this->connection->getConnectionResource();
		if (!$this->ldap->isResource($cr)) {
			//LDAP not available
			$this->logger->debug('LDAP resource not available.', ['app' => 'user_ldap']);
			return false;
		}
		try {
			// try PASSWD extended operation first
			return @$this->invokeLDAPMethod('exopPasswd', $userDN, '', $password) ||
				@$this->invokeLDAPMethod('modReplace', $userDN, $password);
		} catch (ConstraintViolationException $e) {
			throw new HintException('Password change rejected.', \OC::$server->getL10N('user_ldap')->t('Password change rejected. Hint: ') . $e->getMessage(), (int)$e->getCode());
		}
	}

	/**
	 * checks whether the given attributes value is probably a DN
	 *
	 * @param string $attr the attribute in question
	 * @return boolean if so true, otherwise false
	 */
	private function resemblesDN($attr) {
		$resemblingAttributes = [
			'dn',
			'uniquemember',
			'member',
			// memberOf is an "operational" attribute, without a definition in any RFC
			'memberof'
		];
		return in_array($attr, $resemblingAttributes);
	}

	/**
	 * checks whether the given string is probably a DN
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function stringResemblesDN($string) {
		$r = $this->ldap->explodeDN($string, 0);
		// if exploding a DN succeeds and does not end up in
		// an empty array except for $r[count] being 0.
		return (is_array($r) && count($r) > 1);
	}

	/**
	 * returns a DN-string that is cleaned from not domain parts, e.g.
	 * cn=foo,cn=bar,dc=foobar,dc=server,dc=org
	 * becomes dc=foobar,dc=server,dc=org
	 *
	 * @param string $dn
	 * @return string
	 */
	public function getDomainDNFromDN($dn) {
		$allParts = $this->ldap->explodeDN($dn, 0);
		if ($allParts === false) {
			//not a valid DN
			return '';
		}
		$domainParts = [];
		$dcFound = false;
		foreach ($allParts as $part) {
			if (!$dcFound && strpos($part, 'dc=') === 0) {
				$dcFound = true;
			}
			if ($dcFound) {
				$domainParts[] = $part;
			}
		}
		return implode(',', $domainParts);
	}

	/**
	 * returns the LDAP DN for the given internal Nextcloud name of the group
	 *
	 * @param string $name the Nextcloud name in question
	 * @return string|false LDAP DN on success, otherwise false
	 */
	public function groupname2dn($name) {
		return $this->getGroupMapper()->getDNByName($name);
	}

	/**
	 * returns the LDAP DN for the given internal Nextcloud name of the user
	 *
	 * @param string $name the Nextcloud name in question
	 * @return string|false with the LDAP DN on success, otherwise false
	 */
	public function username2dn($name) {
		$fdn = $this->getUserMapper()->getDNByName($name);

		//Check whether the DN belongs to the Base, to avoid issues on multi-
		//server setups
		if (is_string($fdn) && $this->isDNPartOfBase($fdn, $this->connection->ldapBaseUsers)) {
			return $fdn;
		}

		return false;
	}

	/**
	 * returns the internal Nextcloud name for the given LDAP DN of the group, false on DN outside of search DN or failure
	 *
	 * @param string $fdn the dn of the group object
	 * @param string $ldapName optional, the display name of the object
	 * @return string|false with the name to use in Nextcloud, false on DN outside of search DN
	 * @throws \Exception
	 */
	public function dn2groupname($fdn, $ldapName = null) {
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if (!$this->isDNPartOfBase($fdn, $this->connection->ldapBaseGroups)) {
			return false;
		}

		return $this->dn2ocname($fdn, $ldapName, false);
	}

	/**
	 * returns the internal Nextcloud name for the given LDAP DN of the user, false on DN outside of search DN or failure
	 *
	 * @param string $fdn the dn of the user object
	 * @param string $ldapName optional, the display name of the object
	 * @return string|false with with the name to use in Nextcloud
	 * @throws \Exception
	 */
	public function dn2username($fdn, $ldapName = null) {
		//To avoid bypassing the base DN settings under certain circumstances
		//with the group support, check whether the provided DN matches one of
		//the given Bases
		if (!$this->isDNPartOfBase($fdn, $this->connection->ldapBaseUsers)) {
			return false;
		}

		return $this->dn2ocname($fdn, $ldapName, true);
	}

	/**
	 * returns an internal Nextcloud name for the given LDAP DN, false on DN outside of search DN
	 *
	 * @param string $fdn the dn of the user object
	 * @param string|null $ldapName optional, the display name of the object
	 * @param bool $isUser optional, whether it is a user object (otherwise group assumed)
	 * @param bool|null $newlyMapped
	 * @param array|null $record
	 * @return false|string with with the name to use in Nextcloud
	 * @throws \Exception
	 */
	public function dn2ocname($fdn, $ldapName = null, $isUser = true, &$newlyMapped = null, array $record = null) {
		$newlyMapped = false;
		if ($isUser) {
			$mapper = $this->getUserMapper();
			$nameAttribute = $this->connection->ldapUserDisplayName;
			$filter = $this->connection->ldapUserFilter;
		} else {
			$mapper = $this->getGroupMapper();
			$nameAttribute = $this->connection->ldapGroupDisplayName;
			$filter = $this->connection->ldapGroupFilter;
		}

		//let's try to retrieve the Nextcloud name from the mappings table
		$ncName = $mapper->getNameByDN($fdn);
		if (is_string($ncName)) {
			return $ncName;
		}

		//second try: get the UUID and check if it is known. Then, update the DN and return the name.
		$uuid = $this->getUUID($fdn, $isUser, $record);
		if (is_string($uuid)) {
			$ncName = $mapper->getNameByUUID($uuid);
			if (is_string($ncName)) {
				$mapper->setDNbyUUID($fdn, $uuid);
				return $ncName;
			}
		} else {
			//If the UUID can't be detected something is foul.
			$this->logger->debug('Cannot determine UUID for ' . $fdn . '. Skipping.', ['app' => 'user_ldap']);
			return false;
		}

		if (is_null($ldapName)) {
			$ldapName = $this->readAttribute($fdn, $nameAttribute, $filter);
			if (!isset($ldapName[0]) || empty($ldapName[0])) {
				$this->logger->debug('No or empty name for ' . $fdn . ' with filter ' . $filter . '.', ['app' => 'user_ldap']);
				return false;
			}
			$ldapName = $ldapName[0];
		}

		if ($isUser) {
			$usernameAttribute = (string)$this->connection->ldapExpertUsernameAttr;
			if ($usernameAttribute !== '') {
				$username = $this->readAttribute($fdn, $usernameAttribute);
				if (!isset($username[0]) || empty($username[0])) {
					$this->logger->debug('No or empty username (' . $usernameAttribute . ') for ' . $fdn . '.', ['app' => 'user_ldap']);
					return false;
				}
				$username = $username[0];
			} else {
				$username = $uuid;
			}
			try {
				$intName = $this->sanitizeUsername($username);
			} catch (\InvalidArgumentException $e) {
				$this->logger->warning('Error sanitizing username: ' . $e->getMessage(), [
					'exception' => $e,
				]);
				// we don't attempt to set a username here. We can go for
				// for an alternative 4 digit random number as we would append
				// otherwise, however it's likely not enough space in bigger
				// setups, and most importantly: this is not intended.
				return false;
			}
		} else {
			$intName = $this->sanitizeGroupIDCandidate($ldapName);
		}

		//a new user/group! Add it only if it doesn't conflict with other backend's users or existing groups
		//disabling Cache is required to avoid that the new user is cached as not-existing in fooExists check
		//NOTE: mind, disabling cache affects only this instance! Using it
		// outside of core user management will still cache the user as non-existing.
		$originalTTL = $this->connection->ldapCacheTTL;
		$this->connection->setConfiguration(['ldapCacheTTL' => 0]);
		if ($intName !== ''
			&& (($isUser && !$this->ncUserManager->userExists($intName))
				|| (!$isUser && !\OC::$server->getGroupManager()->groupExists($intName))
			)
		) {
			$this->connection->setConfiguration(['ldapCacheTTL' => $originalTTL]);
			$newlyMapped = $this->mapAndAnnounceIfApplicable($mapper, $fdn, $intName, $uuid, $isUser);
			if ($newlyMapped) {
				return $intName;
			}
		}

		$this->connection->setConfiguration(['ldapCacheTTL' => $originalTTL]);
		$altName = $this->createAltInternalOwnCloudName($intName, $isUser);
		if (is_string($altName)) {
			if ($this->mapAndAnnounceIfApplicable($mapper, $fdn, $altName, $uuid, $isUser)) {
				$newlyMapped = true;
				return $altName;
			}
		}

		//if everything else did not help..
		$this->logger->info('Could not create unique name for ' . $fdn . '.', ['app' => 'user_ldap']);
		return false;
	}

	public function mapAndAnnounceIfApplicable(
		AbstractMapping $mapper,
		string $fdn,
		string $name,
		string $uuid,
		bool $isUser
	): bool {
		if ($mapper->map($fdn, $name, $uuid)) {
			if ($this->ncUserManager instanceof PublicEmitter && $isUser) {
				$this->cacheUserExists($name);
				$this->ncUserManager->emit('\OC\User', 'assignedUserId', [$name]);
			} elseif (!$isUser) {
				$this->cacheGroupExists($name);
			}
			return true;
		}
		return false;
	}

	/**
	 * gives back the user names as they are used ownClod internally
	 *
	 * @param array $ldapUsers as returned by fetchList()
	 * @return array an array with the user names to use in Nextcloud
	 *
	 * gives back the user names as they are used ownClod internally
	 * @throws \Exception
	 */
	public function nextcloudUserNames($ldapUsers) {
		return $this->ldap2NextcloudNames($ldapUsers, true);
	}

	/**
	 * gives back the group names as they are used ownClod internally
	 *
	 * @param array $ldapGroups as returned by fetchList()
	 * @return array an array with the group names to use in Nextcloud
	 *
	 * gives back the group names as they are used ownClod internally
	 * @throws \Exception
	 */
	public function nextcloudGroupNames($ldapGroups) {
		return $this->ldap2NextcloudNames($ldapGroups, false);
	}

	/**
	 * @param array[] $ldapObjects as returned by fetchList()
	 * @throws \Exception
	 */
	private function ldap2NextcloudNames(array $ldapObjects, bool $isUsers): array {
		if ($isUsers) {
			$nameAttribute = $this->connection->ldapUserDisplayName;
			$sndAttribute = $this->connection->ldapUserDisplayName2;
		} else {
			$nameAttribute = $this->connection->ldapGroupDisplayName;
			$sndAttribute = null;
		}
		$nextcloudNames = [];

		foreach ($ldapObjects as $ldapObject) {
			$nameByLDAP = $ldapObject[$nameAttribute][0] ?? null;

			$ncName = $this->dn2ocname($ldapObject['dn'][0], $nameByLDAP, $isUsers);
			if ($ncName) {
				$nextcloudNames[] = $ncName;
				if ($isUsers) {
					$this->updateUserState($ncName);
					//cache the user names so it does not need to be retrieved
					//again later (e.g. sharing dialogue).
					if (is_null($nameByLDAP)) {
						continue;
					}
					$sndName = $ldapObject[$sndAttribute][0] ?? '';
					$this->cacheUserDisplayName($ncName, $nameByLDAP, $sndName);
				} elseif ($nameByLDAP !== null) {
					$this->cacheGroupDisplayName($ncName, $nameByLDAP);
				}
			}
		}
		return $nextcloudNames;
	}

	/**
	 * removes the deleted-flag of a user if it was set
	 *
	 * @param string $ncname
	 * @throws \Exception
	 */
	public function updateUserState($ncname): void {
		$user = $this->userManager->get($ncname);
		if ($user instanceof OfflineUser) {
			$user->unmark();
		}
	}

	/**
	 * caches the user display name
	 *
	 * @param string $ocName the internal Nextcloud username
	 * @param string|false $home the home directory path
	 */
	public function cacheUserHome(string $ocName, $home): void {
		$cacheKey = 'getHome' . $ocName;
		$this->connection->writeToCache($cacheKey, $home);
	}

	/**
	 * caches a user as existing
	 */
	public function cacheUserExists(string $ocName): void {
		$this->connection->writeToCache('userExists' . $ocName, true);
	}

	/**
	 * caches a group as existing
	 */
	public function cacheGroupExists(string $gid): void {
		$this->connection->writeToCache('groupExists' . $gid, true);
	}

	/**
	 * caches the user display name
	 *
	 * @param string $ocName the internal Nextcloud username
	 * @param string $displayName the display name
	 * @param string $displayName2 the second display name
	 * @throws \Exception
	 */
	public function cacheUserDisplayName(string $ocName, string $displayName, string $displayName2 = ''): void {
		$user = $this->userManager->get($ocName);
		if ($user === null) {
			return;
		}
		$displayName = $user->composeAndStoreDisplayName($displayName, $displayName2);
		$cacheKeyTrunk = 'getDisplayName';
		$this->connection->writeToCache($cacheKeyTrunk . $ocName, $displayName);
	}

	public function cacheGroupDisplayName(string $ncName, string $displayName): void {
		$cacheKey = 'group_getDisplayName' . $ncName;
		$this->connection->writeToCache($cacheKey, $displayName);
	}

	/**
	 * creates a unique name for internal Nextcloud use for users. Don't call it directly.
	 *
	 * @param string $name the display name of the object
	 * @return string|false with with the name to use in Nextcloud or false if unsuccessful
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, true)
	 */
	private function _createAltInternalOwnCloudNameForUsers(string $name) {
		$attempts = 0;
		//while loop is just a precaution. If a name is not generated within
		//20 attempts, something else is very wrong. Avoids infinite loop.
		while ($attempts < 20) {
			$altName = $name . '_' . rand(1000, 9999);
			if (!$this->ncUserManager->userExists($altName)) {
				return $altName;
			}
			$attempts++;
		}
		return false;
	}

	/**
	 * creates a unique name for internal Nextcloud use for groups. Don't call it directly.
	 *
	 * @param string $name the display name of the object
	 * @return string|false with with the name to use in Nextcloud or false if unsuccessful.
	 *
	 * Instead of using this method directly, call
	 * createAltInternalOwnCloudName($name, false)
	 *
	 * Group names are also used as display names, so we do a sequential
	 * numbering, e.g. Developers_42 when there are 41 other groups called
	 * "Developers"
	 */
	private function _createAltInternalOwnCloudNameForGroups(string $name) {
		$usedNames = $this->getGroupMapper()->getNamesBySearch($name, "", '_%');
		if (count($usedNames) === 0) {
			$lastNo = 1; //will become name_2
		} else {
			natsort($usedNames);
			$lastName = array_pop($usedNames);
			$lastNo = (int)substr($lastName, strrpos($lastName, '_') + 1);
		}
		$altName = $name . '_' . (string)($lastNo + 1);
		unset($usedNames);

		$attempts = 1;
		while ($attempts < 21) {
			// Check to be really sure it is unique
			// while loop is just a precaution. If a name is not generated within
			// 20 attempts, something else is very wrong. Avoids infinite loop.
			if (!\OC::$server->getGroupManager()->groupExists($altName)) {
				return $altName;
			}
			$altName = $name . '_' . ($lastNo + $attempts);
			$attempts++;
		}
		return false;
	}

	/**
	 * creates a unique name for internal Nextcloud use.
	 *
	 * @param string $name the display name of the object
	 * @param bool $isUser whether name should be created for a user (true) or a group (false)
	 * @return string|false with with the name to use in Nextcloud or false if unsuccessful
	 */
	private function createAltInternalOwnCloudName(string $name, bool $isUser) {
		// ensure there is space for the "_1234" suffix
		if (strlen($name) > 59) {
			$name = substr($name, 0, 59);
		}

		$originalTTL = $this->connection->ldapCacheTTL;
		$this->connection->setConfiguration(['ldapCacheTTL' => 0]);
		if ($isUser) {
			$altName = $this->_createAltInternalOwnCloudNameForUsers($name);
		} else {
			$altName = $this->_createAltInternalOwnCloudNameForGroups($name);
		}
		$this->connection->setConfiguration(['ldapCacheTTL' => $originalTTL]);

		return $altName;
	}

	/**
	 * fetches a list of users according to a provided loginName and utilizing
	 * the login filter.
	 */
	public function fetchUsersByLoginName(string $loginName, array $attributes = ['dn']): array {
		$loginName = $this->escapeFilterPart($loginName);
		$filter = str_replace('%uid', $loginName, $this->connection->ldapLoginFilter);
		return $this->fetchListOfUsers($filter, $attributes);
	}

	/**
	 * counts the number of users according to a provided loginName and
	 * utilizing the login filter.
	 *
	 * @param string $loginName
	 * @return false|int
	 */
	public function countUsersByLoginName($loginName) {
		$loginName = $this->escapeFilterPart($loginName);
		$filter = str_replace('%uid', $loginName, $this->connection->ldapLoginFilter);
		return $this->countUsers($filter);
	}

	/**
	 * @throws \Exception
	 */
	public function fetchListOfUsers(string $filter, array $attr, int $limit = null, int $offset = null, bool $forceApplyAttributes = false): array {
		$ldapRecords = $this->searchUsers($filter, $attr, $limit, $offset);
		$recordsToUpdate = $ldapRecords;
		if (!$forceApplyAttributes) {
			$isBackgroundJobModeAjax = $this->config
					->getAppValue('core', 'backgroundjobs_mode', 'ajax') === 'ajax';
			$listOfDNs = array_reduce($ldapRecords, function ($listOfDNs, $entry) {
				$listOfDNs[] = $entry['dn'][0];
				return $listOfDNs;
			}, []);
			$idsByDn = $this->getUserMapper()->getListOfIdsByDn($listOfDNs);
			$recordsToUpdate = array_filter($ldapRecords, function ($record) use ($isBackgroundJobModeAjax, $idsByDn) {
				$newlyMapped = false;
				$uid = $idsByDn[$record['dn'][0]] ?? null;
				if ($uid === null) {
					$uid = $this->dn2ocname($record['dn'][0], null, true, $newlyMapped, $record);
				}
				if (is_string($uid)) {
					$this->cacheUserExists($uid);
				}
				return ($uid !== false) && ($newlyMapped || $isBackgroundJobModeAjax);
			});
		}
		$this->batchApplyUserAttributes($recordsToUpdate);
		return $this->fetchList($ldapRecords, $this->manyAttributes($attr));
	}

	/**
	 * provided with an array of LDAP user records the method will fetch the
	 * user object and requests it to process the freshly fetched attributes and
	 * and their values
	 *
	 * @throws \Exception
	 */
	public function batchApplyUserAttributes(array $ldapRecords): void {
		$displayNameAttribute = strtolower((string)$this->connection->ldapUserDisplayName);
		foreach ($ldapRecords as $userRecord) {
			if (!isset($userRecord[$displayNameAttribute])) {
				// displayName is obligatory
				continue;
			}
			$ocName = $this->dn2ocname($userRecord['dn'][0], null, true);
			if ($ocName === false) {
				continue;
			}
			$this->updateUserState($ocName);
			$user = $this->userManager->get($ocName);
			if ($user !== null) {
				$user->processAttributes($userRecord);
			} else {
				$this->logger->debug(
					"The ldap user manager returned null for $ocName",
					['app' => 'user_ldap']
				);
			}
		}
	}

	/**
	 * @return array[]
	 */
	public function fetchListOfGroups(string $filter, array $attr, int $limit = null, int $offset = null): array {
		$groupRecords = $this->searchGroups($filter, $attr, $limit, $offset);

		$listOfDNs = array_reduce($groupRecords, function ($listOfDNs, $entry) {
			$listOfDNs[] = $entry['dn'][0];
			return $listOfDNs;
		}, []);
		$idsByDn = $this->getGroupMapper()->getListOfIdsByDn($listOfDNs);

		array_walk($groupRecords, function (array $record) use ($idsByDn) {
			$newlyMapped = false;
			$gid = $idsByDn[$record['dn'][0]] ?? null;
			if ($gid === null) {
				$gid = $this->dn2ocname($record['dn'][0], null, false, $newlyMapped, $record);
			}
			if (!$newlyMapped && is_string($gid)) {
				$this->cacheGroupExists($gid);
			}
		});
		return $this->fetchList($groupRecords, $this->manyAttributes($attr));
	}

	private function fetchList(array $list, bool $manyAttributes): array {
		if ($manyAttributes) {
			return $list;
		} else {
			$list = array_reduce($list, function ($carry, $item) {
				$attribute = array_keys($item)[0];
				$carry[] = $item[$attribute][0];
				return $carry;
			}, []);
			return array_unique($list, SORT_LOCALE_STRING);
		}
	}

	/**
	 * @throws ServerNotAvailableException
	 */
	public function searchUsers(string $filter, array $attr = null, int $limit = null, int $offset = null): array {
		$result = [];
		foreach ($this->connection->ldapBaseUsers as $base) {
			$result = array_merge($result, $this->search($filter, $base, $attr, $limit, $offset));
		}
		return $result;
	}

	/**
	 * @param string[] $attr
	 * @return false|int
	 * @throws ServerNotAvailableException
	 */
	public function countUsers(string $filter, array $attr = ['dn'], int $limit = null, int $offset = null) {
		$result = false;
		foreach ($this->connection->ldapBaseUsers as $base) {
			$count = $this->count($filter, [$base], $attr, $limit ?? 0, $offset ?? 0);
			$result = is_int($count) ? (int)$result + $count : $result;
		}
		return $result;
	}

	/**
	 * executes an LDAP search, optimized for Groups
	 *
	 * @param ?string[] $attr optional, when certain attributes shall be filtered out
	 *
	 * Executes an LDAP search
	 * @throws ServerNotAvailableException
	 */
	public function searchGroups(string $filter, array $attr = null, int $limit = null, int $offset = null): array {
		$result = [];
		foreach ($this->connection->ldapBaseGroups as $base) {
			$result = array_merge($result, $this->search($filter, $base, $attr, $limit, $offset));
		}
		return $result;
	}

	/**
	 * returns the number of available groups
	 *
	 * @return int|bool
	 * @throws ServerNotAvailableException
	 */
	public function countGroups(string $filter, array $attr = ['dn'], int $limit = null, int $offset = null) {
		$result = false;
		foreach ($this->connection->ldapBaseGroups as $base) {
			$count = $this->count($filter, [$base], $attr, $limit ?? 0, $offset ?? 0);
			$result = is_int($count) ? (int)$result + $count : $result;
		}
		return $result;
	}

	/**
	 * returns the number of available objects on the base DN
	 *
	 * @return int|bool
	 * @throws ServerNotAvailableException
	 */
	public function countObjects(int $limit = null, int $offset = null) {
		$result = false;
		foreach ($this->connection->ldapBase as $base) {
			$count = $this->count('objectclass=*', [$base], ['dn'], $limit ?? 0, $offset ?? 0);
			$result = is_int($count) ? (int)$result + $count : $result;
		}
		return $result;
	}

	/**
	 * Returns the LDAP handler
	 *
	 * @throws \OC\ServerNotAvailableException
	 */

	/**
	 * @param mixed[] $arguments
	 * @return mixed
	 * @throws \OC\ServerNotAvailableException
	 */
	private function invokeLDAPMethod(string $command, ...$arguments) {
		if ($command == 'controlPagedResultResponse') {
			// php no longer supports call-time pass-by-reference
			// thus cannot support controlPagedResultResponse as the third argument
			// is a reference
			throw new \InvalidArgumentException('Invoker does not support controlPagedResultResponse, call LDAP Wrapper directly instead.');
		}
		if (!method_exists($this->ldap, $command)) {
			return null;
		}
		array_unshift($arguments, $this->connection->getConnectionResource());
		$doMethod = function () use ($command, &$arguments) {
			return call_user_func_array([$this->ldap, $command], $arguments);
		};
		try {
			$ret = $doMethod();
		} catch (ServerNotAvailableException $e) {
			/* Server connection lost, attempt to reestablish it
			 * Maybe implement exponential backoff?
			 * This was enough to get solr indexer working which has large delays between LDAP fetches.
			 */
			$this->logger->debug("Connection lost on $command, attempting to reestablish.", ['app' => 'user_ldap']);
			$this->connection->resetConnectionResource();
			$cr = $this->connection->getConnectionResource();

			if (!$this->ldap->isResource($cr)) {
				// Seems like we didn't find any resource.
				$this->logger->debug("Could not $command, because resource is missing.", ['app' => 'user_ldap']);
				throw $e;
			}

			$arguments[0] = $cr;
			$ret = $doMethod();
		}
		return $ret;
	}

	/**
	 * retrieved. Results will according to the order in the array.
	 *
	 * @param string $filter
	 * @param string $base
	 * @param string[] $attr
	 * @param int|null $limit optional, maximum results to be counted
	 * @param int|null $offset optional, a starting point
	 * @return array|false array with the search result as first value and pagedSearchOK as
	 * second | false if not successful
	 * @throws ServerNotAvailableException
	 */
	private function executeSearch(
		string $filter,
		string $base,
		?array &$attr,
		?int $limit,
		?int $offset
	) {
		// See if we have a resource, in case not cancel with message
		$cr = $this->connection->getConnectionResource();
		if (!$this->ldap->isResource($cr)) {
			// Seems like we didn't find any resource.
			// Return an empty array just like before.
			$this->logger->debug('Could not search, because resource is missing.', ['app' => 'user_ldap']);
			return false;
		}

		//check whether paged search should be attempted
		try {
			$pagedSearchOK = $this->initPagedSearch($filter, $base, $attr, (int)$limit, (int)$offset);
		} catch (NoMoreResults $e) {
			// beyond last results page
			return false;
		}

		$sr = $this->invokeLDAPMethod('search', $base, $filter, $attr);
		$error = $this->ldap->errno($this->connection->getConnectionResource());
		if (!$this->ldap->isResource($sr) || $error !== 0) {
			$this->logger->error('Attempt for Paging?  ' . print_r($pagedSearchOK, true), ['app' => 'user_ldap']);
			return false;
		}

		return [$sr, $pagedSearchOK];
	}

	/**
	 * processes an LDAP paged search operation
	 *
	 * @param resource|\LDAP\Result|resource[]|\LDAP\Result[] $sr the array containing the LDAP search resources
	 * @param int $foundItems number of results in the single search operation
	 * @param int $limit maximum results to be counted
	 * @param bool $pagedSearchOK whether a paged search has been executed
	 * @param bool $skipHandling required for paged search when cookies to
	 * prior results need to be gained
	 * @return bool cookie validity, true if we have more pages, false otherwise.
	 * @throws ServerNotAvailableException
	 */
	private function processPagedSearchStatus(
		$sr,
		int $foundItems,
		int $limit,
		bool $pagedSearchOK,
		bool $skipHandling
	): bool {
		$cookie = '';
		if ($pagedSearchOK) {
			$cr = $this->connection->getConnectionResource();
			if ($this->ldap->controlPagedResultResponse($cr, $sr, $cookie)) {
				$this->lastCookie = $cookie;
			}

			//browsing through prior pages to get the cookie for the new one
			if ($skipHandling) {
				return false;
			}
			// if count is bigger, then the server does not support
			// paged search. Instead, he did a normal search. We set a
			// flag here, so the callee knows how to deal with it.
			if ($foundItems <= $limit) {
				$this->pagedSearchedSuccessful = true;
			}
		} else {
			if ((int)$this->connection->ldapPagingSize !== 0) {
				$this->logger->debug(
					'Paged search was not available',
					['app' => 'user_ldap']
				);
			}
		}
		/* ++ Fixing RHDS searches with pages with zero results ++
		 * Return cookie status. If we don't have more pages, with RHDS
		 * cookie is null, with openldap cookie is an empty string and
		 * to 386ds '0' is a valid cookie. Even if $iFoundItems == 0
		 */
		return !empty($cookie) || $cookie === '0';
	}

	/**
	 * executes an LDAP search, but counts the results only
	 *
	 * @param string $filter the LDAP filter for the search
	 * @param array $bases an array containing the LDAP subtree(s) that shall be searched
	 * @param ?string[] $attr optional, array, one or more attributes that shall be
	 * retrieved. Results will according to the order in the array.
	 * @param int $limit maximum results to be counted, 0 means no limit
	 * @param int $offset a starting point, defaults to 0
	 * @param bool $skipHandling indicates whether the pages search operation is
	 * completed
	 * @return int|false Integer or false if the search could not be initialized
	 * @throws ServerNotAvailableException
	 */
	private function count(
		string $filter,
		array $bases,
		array $attr = null,
		int $limit = 0,
		int $offset = 0,
		bool $skipHandling = false
	) {
		$this->logger->debug('Count filter: {filter}', [
			'app' => 'user_ldap',
			'filter' => $filter
		]);

		$limitPerPage = (int)$this->connection->ldapPagingSize;
		if ($limit < $limitPerPage && $limit > 0) {
			$limitPerPage = $limit;
		}

		$counter = 0;
		$count = null;
		$this->connection->getConnectionResource();

		foreach ($bases as $base) {
			do {
				$search = $this->executeSearch($filter, $base, $attr, $limitPerPage, $offset);
				if ($search === false) {
					return $counter > 0 ? $counter : false;
				}
				[$sr, $pagedSearchOK] = $search;

				/* ++ Fixing RHDS searches with pages with zero results ++
				 * countEntriesInSearchResults() method signature changed
				 * by removing $limit and &$hasHitLimit parameters
				 */
				$count = $this->countEntriesInSearchResults($sr);
				$counter += $count;

				$hasMorePages = $this->processPagedSearchStatus($sr, $count, $limitPerPage, $pagedSearchOK, $skipHandling);
				$offset += $limitPerPage;
				/* ++ Fixing RHDS searches with pages with zero results ++
				 * Continue now depends on $hasMorePages value
				 */
				$continue = $pagedSearchOK && $hasMorePages;
			} while ($continue && ($limit <= 0 || $limit > $counter));
		}

		return $counter;
	}

	/**
	 * @param resource|\LDAP\Result|resource[]|\LDAP\Result[] $sr
	 * @return int
	 * @throws ServerNotAvailableException
	 */
	private function countEntriesInSearchResults($sr): int {
		return (int)$this->invokeLDAPMethod('countEntries', $sr);
	}

	/**
	 * Executes an LDAP search
	 *
	 * @throws ServerNotAvailableException
	 */
	public function search(
		string $filter,
		string $base,
		?array $attr = null,
		?int $limit = null,
		?int $offset = null,
		bool $skipHandling = false
	): array {
		$limitPerPage = (int)$this->connection->ldapPagingSize;
		if (!is_null($limit) && $limit < $limitPerPage && $limit > 0) {
			$limitPerPage = $limit;
		}

		/* ++ Fixing RHDS searches with pages with zero results ++
		 * As we can have pages with zero results and/or pages with less
		 * than $limit results but with a still valid server 'cookie',
		 * loops through until we get $continue equals true and
		 * $findings['count'] < $limit
		 */
		$findings = [];
		$offset = $offset ?? 0;
		$savedoffset = $offset;
		$iFoundItems = 0;

		do {
			$search = $this->executeSearch($filter, $base, $attr, $limitPerPage, $offset);
			if ($search === false) {
				return [];
			}
			[$sr, $pagedSearchOK] = $search;

			if ($skipHandling) {
				//i.e. result do not need to be fetched, we just need the cookie
				//thus pass 1 or any other value as $iFoundItems because it is not
				//used
				$this->processPagedSearchStatus($sr, 1, $limitPerPage, $pagedSearchOK, $skipHandling);
				return [];
			}

			$findings = array_merge($findings, $this->invokeLDAPMethod('getEntries', $sr));
			$iFoundItems = max($iFoundItems, $findings['count']);
			unset($findings['count']);

			$continue = $this->processPagedSearchStatus($sr, $iFoundItems, $limitPerPage, $pagedSearchOK, $skipHandling);
			$offset += $limitPerPage;
		} while ($continue && $pagedSearchOK && ($limit === null || count($findings) < $limit));

		// resetting offset
		$offset = $savedoffset;

		if (!is_null($attr)) {
			$selection = [];
			$i = 0;
			foreach ($findings as $item) {
				if (!is_array($item)) {
					continue;
				}
				$item = \OCP\Util::mb_array_change_key_case($item, MB_CASE_LOWER, 'UTF-8');
				foreach ($attr as $key) {
					if (isset($item[$key])) {
						if (is_array($item[$key]) && isset($item[$key]['count'])) {
							unset($item[$key]['count']);
						}
						if ($key !== 'dn') {
							if ($this->resemblesDN($key)) {
								$selection[$i][$key] = $this->helper->sanitizeDN($item[$key]);
							} elseif ($key === 'objectguid' || $key === 'guid') {
								$selection[$i][$key] = [$this->convertObjectGUID2Str($item[$key][0])];
							} else {
								$selection[$i][$key] = $item[$key];
							}
						} else {
							$selection[$i][$key] = [$this->helper->sanitizeDN($item[$key])];
						}
					}
				}
				$i++;
			}
			$findings = $selection;
		}
		//we slice the findings, when
		//a) paged search unsuccessful, though attempted
		//b) no paged search, but limit set
		if ((!$this->getPagedSearchResultState()
				&& $pagedSearchOK)
			|| (
				!$pagedSearchOK
				&& !is_null($limit)
			)
		) {
			$findings = array_slice($findings, $offset, $limit);
		}
		return $findings;
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function sanitizeUsername($name) {
		$name = trim($name);

		if ($this->connection->ldapIgnoreNamingRules) {
			return $name;
		}

		// Use htmlentities to get rid of accents
		$name = htmlentities($name, ENT_NOQUOTES, 'UTF-8');

		// Remove accents
		$name = preg_replace('#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $name);
		// Remove ligatures
		$name = preg_replace('#&([A-Za-z]{2})(?:lig);#', '\1', $name);
		// Remove unknown leftover entities
		$name = preg_replace('#&[^;]+;#', '', $name);

		// Replacements
		$name = str_replace(' ', '_', $name);

		// Every remaining disallowed characters will be removed
		$name = preg_replace('/[^a-zA-Z0-9_.@-]/u', '', $name);

		if (strlen($name) > 64) {
			$name = (string)hash('sha256', $name, false);
		}

		if ($name === '') {
			throw new \InvalidArgumentException('provided name template for username does not contain any allowed characters');
		}

		return $name;
	}

	public function sanitizeGroupIDCandidate(string $candidate): string {
		$candidate = trim($candidate);
		if (strlen($candidate) > 64) {
			$candidate = (string)hash('sha256', $candidate, false);
		}
		if ($candidate === '') {
			throw new \InvalidArgumentException('provided name template for username does not contain any allowed characters');
		}

		return $candidate;
	}

	/**
	 * escapes (user provided) parts for LDAP filter
	 *
	 * @param string $input , the provided value
	 * @param bool $allowAsterisk whether in * at the beginning should be preserved
	 * @return string the escaped string
	 */
	public function escapeFilterPart($input, $allowAsterisk = false): string {
		$asterisk = '';
		if ($allowAsterisk && strlen($input) > 0 && $input[0] === '*') {
			$asterisk = '*';
			$input = mb_substr($input, 1, null, 'UTF-8');
		}
		$search = ['*', '\\', '(', ')'];
		$replace = ['\\*', '\\\\', '\\(', '\\)'];
		return $asterisk . str_replace($search, $replace, $input);
	}

	/**
	 * combines the input filters with AND
	 *
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 */
	public function combineFilterWithAnd($filters): string {
		return $this->combineFilter($filters, '&');
	}

	/**
	 * combines the input filters with OR
	 *
	 * @param string[] $filters the filters to connect
	 * @return string the combined filter
	 * Combines Filter arguments with OR
	 */
	public function combineFilterWithOr($filters) {
		return $this->combineFilter($filters, '|');
	}

	/**
	 * combines the input filters with given operator
	 *
	 * @param string[] $filters the filters to connect
	 * @param string $operator either & or |
	 * @return string the combined filter
	 */
	private function combineFilter(array $filters, string $operator): string {
		$combinedFilter = '(' . $operator;
		foreach ($filters as $filter) {
			if ($filter !== '' && $filter[0] !== '(') {
				$filter = '(' . $filter . ')';
			}
			$combinedFilter .= $filter;
		}
		$combinedFilter .= ')';
		return $combinedFilter;
	}

	/**
	 * creates a filter part for to perform search for users
	 *
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForUserSearch($search): string {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForUserSearch,
			$this->connection->ldapUserDisplayName);
	}

	/**
	 * creates a filter part for to perform search for groups
	 *
	 * @param string $search the search term
	 * @return string the final filter part to use in LDAP searches
	 */
	public function getFilterPartForGroupSearch($search): string {
		return $this->getFilterPartForSearch($search,
			$this->connection->ldapAttributesForGroupSearch,
			$this->connection->ldapGroupDisplayName);
	}

	/**
	 * creates a filter part for searches by splitting up the given search
	 * string into single words
	 *
	 * @param string $search the search term
	 * @param string[]|null|'' $searchAttributes needs to have at least two attributes,
	 * otherwise it does not make sense :)
	 * @return string the final filter part to use in LDAP searches
	 * @throws DomainException
	 */
	private function getAdvancedFilterPartForSearch(string $search, $searchAttributes): string {
		if (!is_array($searchAttributes) || count($searchAttributes) < 2) {
			throw new DomainException('searchAttributes must be an array with at least two string');
		}
		$searchWords = explode(' ', trim($search));
		$wordFilters = [];
		foreach ($searchWords as $word) {
			$word = $this->prepareSearchTerm($word);
			//every word needs to appear at least once
			$wordMatchOneAttrFilters = [];
			foreach ($searchAttributes as $attr) {
				$wordMatchOneAttrFilters[] = $attr . '=' . $word;
			}
			$wordFilters[] = $this->combineFilterWithOr($wordMatchOneAttrFilters);
		}
		return $this->combineFilterWithAnd($wordFilters);
	}

	/**
	 * creates a filter part for searches
	 *
	 * @param string $search the search term
	 * @param string[]|null|'' $searchAttributes
	 * @param string $fallbackAttribute a fallback attribute in case the user
	 * did not define search attributes. Typically the display name attribute.
	 * @return string the final filter part to use in LDAP searches
	 */
	private function getFilterPartForSearch(string $search, $searchAttributes, string $fallbackAttribute): string {
		$filter = [];
		$haveMultiSearchAttributes = (is_array($searchAttributes) && count($searchAttributes) > 0);
		if ($haveMultiSearchAttributes && strpos(trim($search), ' ') !== false) {
			try {
				return $this->getAdvancedFilterPartForSearch($search, $searchAttributes);
			} catch (DomainException $e) {
				// Creating advanced filter for search failed, falling back to simple method. Edge case, but valid.
			}
		}

		$search = $this->prepareSearchTerm($search);
		if (!is_array($searchAttributes) || count($searchAttributes) === 0) {
			if ($fallbackAttribute === '') {
				return '';
			}
			$filter[] = $fallbackAttribute . '=' . $search;
		} else {
			foreach ($searchAttributes as $attribute) {
				$filter[] = $attribute . '=' . $search;
			}
		}
		if (count($filter) === 1) {
			return '(' . $filter[0] . ')';
		}
		return $this->combineFilterWithOr($filter);
	}

	/**
	 * returns the search term depending on whether we are allowed
	 * list users found by ldap with the current input appended by
	 * a *
	 */
	private function prepareSearchTerm(string $term): string {
		$config = \OC::$server->getConfig();

		$allowEnum = $config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');

		$result = $term;
		if ($term === '') {
			$result = '*';
		} elseif ($allowEnum !== 'no') {
			$result = $term . '*';
		}
		return $result;
	}

	/**
	 * returns the filter used for counting users
	 */
	public function getFilterForUserCount(): string {
		$filter = $this->combineFilterWithAnd([
			$this->connection->ldapUserFilter,
			$this->connection->ldapUserDisplayName . '=*'
		]);

		return $filter;
	}

	/**
	 * @param string $name
	 * @param string $password
	 * @return bool
	 */
	public function areCredentialsValid($name, $password) {
		$name = $this->helper->DNasBaseParameter($name);
		$testConnection = clone $this->connection;
		$credentials = [
			'ldapAgentName' => $name,
			'ldapAgentPassword' => $password
		];
		if (!$testConnection->setConfiguration($credentials)) {
			return false;
		}
		return $testConnection->bind();
	}

	/**
	 * reverse lookup of a DN given a known UUID
	 *
	 * @param string $uuid
	 * @return string
	 * @throws \Exception
	 */
	public function getUserDnByUuid($uuid) {
		$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		$filter = $this->connection->ldapUserFilter;
		$bases = $this->connection->ldapBaseUsers;

		if ($this->connection->ldapUuidUserAttribute === 'auto' && $uuidOverride === '') {
			// Sacrebleu! The UUID attribute is unknown :( We need first an
			// existing DN to be able to reliably detect it.
			foreach ($bases as $base) {
				$result = $this->search($filter, $base, ['dn'], 1);
				if (!isset($result[0]) || !isset($result[0]['dn'])) {
					continue;
				}
				$dn = $result[0]['dn'][0];
				if ($hasFound = $this->detectUuidAttribute($dn, true)) {
					break;
				}
			}
			if (!isset($hasFound) || !$hasFound) {
				throw new \Exception('Cannot determine UUID attribute');
			}
		} else {
			// The UUID attribute is either known or an override is given.
			// By calling this method we ensure that $this->connection->$uuidAttr
			// is definitely set
			if (!$this->detectUuidAttribute('', true)) {
				throw new \Exception('Cannot determine UUID attribute');
			}
		}

		$uuidAttr = $this->connection->ldapUuidUserAttribute;
		if ($uuidAttr === 'guid' || $uuidAttr === 'objectguid') {
			$uuid = $this->formatGuid2ForFilterUser($uuid);
		}

		$filter = $uuidAttr . '=' . $uuid;
		$result = $this->searchUsers($filter, ['dn'], 2);
		if (isset($result[0]['dn']) && count($result) === 1) {
			// we put the count into account to make sure that this is
			// really unique
			return $result[0]['dn'][0];
		}

		throw new \Exception('Cannot determine UUID attribute');
	}

	/**
	 * auto-detects the directory's UUID attribute
	 *
	 * @param string $dn a known DN used to check against
	 * @param bool $isUser
	 * @param bool $force the detection should be run, even if it is not set to auto
	 * @param array|null $ldapRecord
	 * @return bool true on success, false otherwise
	 * @throws ServerNotAvailableException
	 */
	private function detectUuidAttribute(string $dn, bool $isUser = true, bool $force = false, ?array $ldapRecord = null): bool {
		if ($isUser) {
			$uuidAttr = 'ldapUuidUserAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		} else {
			$uuidAttr = 'ldapUuidGroupAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDGroupAttr;
		}

		if (!$force) {
			if ($this->connection->$uuidAttr !== 'auto') {
				return true;
			} elseif (is_string($uuidOverride) && trim($uuidOverride) !== '') {
				$this->connection->$uuidAttr = $uuidOverride;
				return true;
			}

			$attribute = $this->connection->getFromCache($uuidAttr);
			if ($attribute !== null) {
				$this->connection->$uuidAttr = $attribute;
				return true;
			}
		}

		foreach (self::UUID_ATTRIBUTES as $attribute) {
			if ($ldapRecord !== null) {
				// we have the info from LDAP already, we don't need to talk to the server again
				if (isset($ldapRecord[$attribute])) {
					$this->connection->$uuidAttr = $attribute;
					return true;
				}
			}

			$value = $this->readAttribute($dn, $attribute);
			if (is_array($value) && isset($value[0]) && !empty($value[0])) {
				$this->logger->debug(
					'Setting {attribute} as {subject}',
					[
						'app' => 'user_ldap',
						'attribute' => $attribute,
						'subject' => $uuidAttr
					]
				);
				$this->connection->$uuidAttr = $attribute;
				$this->connection->writeToCache($uuidAttr, $attribute);
				return true;
			}
		}
		$this->logger->debug('Could not autodetect the UUID attribute', ['app' => 'user_ldap']);

		return false;
	}

	/**
	 * @param array|null $ldapRecord
	 * @return false|string
	 * @throws ServerNotAvailableException
	 */
	public function getUUID(string $dn, bool $isUser = true, array $ldapRecord = null) {
		if ($isUser) {
			$uuidAttr = 'ldapUuidUserAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDUserAttr;
		} else {
			$uuidAttr = 'ldapUuidGroupAttribute';
			$uuidOverride = $this->connection->ldapExpertUUIDGroupAttr;
		}

		$uuid = false;
		if ($this->detectUuidAttribute($dn, $isUser, false, $ldapRecord)) {
			$attr = $this->connection->$uuidAttr;
			$uuid = isset($ldapRecord[$attr]) ? $ldapRecord[$attr] : $this->readAttribute($dn, $attr);
			if (!is_array($uuid)
				&& $uuidOverride !== ''
				&& $this->detectUuidAttribute($dn, $isUser, true, $ldapRecord)) {
				$uuid = isset($ldapRecord[$this->connection->$uuidAttr])
					? $ldapRecord[$this->connection->$uuidAttr]
					: $this->readAttribute($dn, $this->connection->$uuidAttr);
			}
			if (is_array($uuid) && !empty($uuid[0])) {
				$uuid = $uuid[0];
			}
		}

		return $uuid;
	}

	/**
	 * converts a binary ObjectGUID into a string representation
	 *
	 * @param string $oguid the ObjectGUID in its binary form as retrieved from AD
	 * @link https://www.php.net/manual/en/function.ldap-get-values-len.php#73198
	 */
	private function convertObjectGUID2Str(string $oguid): string {
		$hex_guid = bin2hex($oguid);
		$hex_guid_to_guid_str = '';
		for ($k = 1; $k <= 4; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for ($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for ($k = 1; $k <= 2; ++$k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);

		return strtoupper($hex_guid_to_guid_str);
	}

	/**
	 * the first three blocks of the string-converted GUID happen to be in
	 * reverse order. In order to use it in a filter, this needs to be
	 * corrected. Furthermore the dashes need to be replaced and \\ prepended
	 * to every two hex figures.
	 *
	 * If an invalid string is passed, it will be returned without change.
	 */
	public function formatGuid2ForFilterUser(string $guid): string {
		$blocks = explode('-', $guid);
		if (count($blocks) !== 5) {
			/*
			 * Why not throw an Exception instead? This method is a utility
			 * called only when trying to figure out whether a "missing" known
			 * LDAP user was or was not renamed on the LDAP server. And this
			 * even on the use case that a reverse lookup is needed (UUID known,
			 * not DN), i.e. when finding users (search dialog, users page,
			 * login, …) this will not be fired. This occurs only if shares from
			 * a users are supposed to be mounted who cannot be found. Throwing
			 * an exception here would kill the experience for a valid, acting
			 * user. Instead we write a log message.
			 */
			$this->logger->info(
				'Passed string does not resemble a valid GUID. Known UUID ' .
				'({uuid}) probably does not match UUID configuration.',
				['app' => 'user_ldap', 'uuid' => $guid]
			);
			return $guid;
		}
		for ($i = 0; $i < 3; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$pairs = array_reverse($pairs);
			$blocks[$i] = implode('', $pairs);
		}
		for ($i = 0; $i < 5; $i++) {
			$pairs = str_split($blocks[$i], 2);
			$blocks[$i] = '\\' . implode('\\', $pairs);
		}
		return implode('', $blocks);
	}

	/**
	 * gets a SID of the domain of the given dn
	 *
	 * @param string $dn
	 * @return string|bool
	 * @throws ServerNotAvailableException
	 */
	public function getSID($dn) {
		$domainDN = $this->getDomainDNFromDN($dn);
		$cacheKey = 'getSID-' . $domainDN;
		$sid = $this->connection->getFromCache($cacheKey);
		if (!is_null($sid)) {
			return $sid;
		}

		$objectSid = $this->readAttribute($domainDN, 'objectsid');
		if (!is_array($objectSid) || empty($objectSid)) {
			$this->connection->writeToCache($cacheKey, false);
			return false;
		}
		$domainObjectSid = $this->convertSID2Str($objectSid[0]);
		$this->connection->writeToCache($cacheKey, $domainObjectSid);

		return $domainObjectSid;
	}

	/**
	 * converts a binary SID into a string representation
	 *
	 * @param string $sid
	 * @return string
	 */
	public function convertSID2Str($sid) {
		// The format of a SID binary string is as follows:
		// 1 byte for the revision level
		// 1 byte for the number n of variable sub-ids
		// 6 bytes for identifier authority value
		// n*4 bytes for n sub-ids
		//
		// Example: 010400000000000515000000a681e50e4d6c6c2bca32055f
		//  Legend: RRNNAAAAAAAAAAAA11111111222222223333333344444444
		$revision = ord($sid[0]);
		$numberSubID = ord($sid[1]);

		$subIdStart = 8; // 1 + 1 + 6
		$subIdLength = 4;
		if (strlen($sid) !== $subIdStart + $subIdLength * $numberSubID) {
			// Incorrect number of bytes present.
			return '';
		}

		// 6 bytes = 48 bits can be represented using floats without loss of
		// precision (see https://gist.github.com/bantu/886ac680b0aef5812f71)
		$iav = number_format(hexdec(bin2hex(substr($sid, 2, 6))), 0, '', '');

		$subIDs = [];
		for ($i = 0; $i < $numberSubID; $i++) {
			$subID = unpack('V', substr($sid, $subIdStart + $subIdLength * $i, $subIdLength));
			$subIDs[] = sprintf('%u', $subID[1]);
		}

		// Result for example above: S-1-5-21-249921958-728525901-1594176202
		return sprintf('S-%d-%s-%s', $revision, $iav, implode('-', $subIDs));
	}

	/**
	 * checks if the given DN is part of the given base DN(s)
	 *
	 * @param string[] $bases array containing the allowed base DN or DNs
	 */
	public function isDNPartOfBase(string $dn, array $bases): bool {
		$belongsToBase = false;
		$bases = $this->helper->sanitizeDN($bases);

		foreach ($bases as $base) {
			$belongsToBase = true;
			if (mb_strripos($dn, $base, 0, 'UTF-8') !== (mb_strlen($dn, 'UTF-8') - mb_strlen($base, 'UTF-8'))) {
				$belongsToBase = false;
			}
			if ($belongsToBase) {
				break;
			}
		}
		return $belongsToBase;
	}

	/**
	 * resets a running Paged Search operation
	 *
	 * @throws ServerNotAvailableException
	 */
	private function abandonPagedSearch(): void {
		if ($this->lastCookie === '') {
			return;
		}
		$this->invokeLDAPMethod('controlPagedResult', 0, false);
		$this->getPagedSearchResultState();
		$this->lastCookie = '';
	}

	/**
	 * checks whether an LDAP paged search operation has more pages that can be
	 * retrieved, typically when offset and limit are provided.
	 *
	 * Be very careful to use it: the last cookie value, which is inspected, can
	 * be reset by other operations. Best, call it immediately after a search(),
	 * searchUsers() or searchGroups() call. count-methods are probably safe as
	 * well. Don't rely on it with any fetchList-method.
	 *
	 * @return bool
	 */
	public function hasMoreResults() {
		if ($this->lastCookie === '') {
			// as in RFC 2696, when all results are returned, the cookie will
			// be empty.
			return false;
		}

		return true;
	}

	/**
	 * Check whether the most recent paged search was successful. It flushed the state var. Use it always after a possible paged search.
	 *
	 * @return boolean|null true on success, null or false otherwise
	 */
	public function getPagedSearchResultState() {
		$result = $this->pagedSearchedSuccessful;
		$this->pagedSearchedSuccessful = null;
		return $result;
	}

	/**
	 * Prepares a paged search, if possible
	 *
	 * @param string $filter the LDAP filter for the search
	 * @param string[] $bases an array containing the LDAP subtree(s) that shall be searched
	 * @param string[] $attr optional, when a certain attribute shall be filtered outside
	 * @param int $limit
	 * @param int $offset
	 * @return bool|true
	 * @throws ServerNotAvailableException
	 * @throws NoMoreResults
	 */
	private function initPagedSearch(
		string $filter,
		string $base,
		?array $attr,
		int $limit,
		int $offset
	): bool {
		$pagedSearchOK = false;
		if ($limit !== 0) {
			$this->logger->debug(
				'initializing paged search for filter {filter}, base {base}, attr {attr}, limit {limit}, offset {offset}',
				[
					'app' => 'user_ldap',
					'filter' => $filter,
					'base' => $base,
					'attr' => $attr,
					'limit' => $limit,
					'offset' => $offset
				]
			);
			// Get the cookie from the search for the previous search, required by LDAP
			if (($this->lastCookie === '') && ($offset > 0)) {
				// no cookie known from a potential previous search. We need
				// to start from 0 to come to the desired page. cookie value
				// of '0' is valid, because 389ds
				$reOffset = ($offset - $limit) < 0 ? 0 : $offset - $limit;
				$this->search($filter, $base, $attr, $limit, $reOffset, true);
				if (!$this->hasMoreResults()) {
					// when the cookie is reset with != 0 offset, there are no further
					// results, so stop.
					throw new NoMoreResults();
				}
			}
			if ($this->lastCookie !== '' && $offset === 0) {
				//since offset = 0, this is a new search. We abandon other searches that might be ongoing.
				$this->abandonPagedSearch();
			}
			$pagedSearchOK = true;
			$this->invokeLDAPMethod('controlPagedResult', $limit, false, $this->lastCookie);
			$this->logger->debug('Ready for a paged search', ['app' => 'user_ldap']);
		/* ++ Fixing RHDS searches with pages with zero results ++
		 * We couldn't get paged searches working with our RHDS for login ($limit = 0),
		 * due to pages with zero results.
		 * So we added "&& !empty($this->lastCookie)" to this test to ignore pagination
		 * if we don't have a previous paged search.
		 */
		} elseif ($this->lastCookie !== '') {
			// a search without limit was requested. However, if we do use
			// Paged Search once, we always must do it. This requires us to
			// initialize it with the configured page size.
			$this->abandonPagedSearch();
			// in case someone set it to 0 … use 500, otherwise no results will
			// be returned.
			$pageSize = (int)$this->connection->ldapPagingSize > 0 ? (int)$this->connection->ldapPagingSize : 500;
			$pagedSearchOK = true;
			$this->invokeLDAPMethod('controlPagedResult', $pageSize, false, $this->lastCookie);
		}

		return $pagedSearchOK;
	}

	/**
	 * Is more than one $attr used for search?
	 *
	 * @param string|string[]|null $attr
	 * @return bool
	 */
	private function manyAttributes($attr): bool {
		if (\is_array($attr)) {
			return \count($attr) > 1;
		}
		return false;
	}
}
