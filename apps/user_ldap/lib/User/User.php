<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Marc Hefter <marchefter@march42.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Staiger <philipp@staiger.it>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\User_LDAP\User;

use OC\Accounts\AccountManager;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Exceptions\AttributeNotSet;
use OCA\User_LDAP\FilesystemHelper;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\Image;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\Notification\IManager as INotificationManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * User
 *
 * represents an LDAP user, gets and holds user-specific information from LDAP
 */
class User {
	/**
	 * @var Access
	 */
	protected $access;
	/**
	 * @var Connection
	 */
	protected $connection;
	/**
	 * @var IConfig
	 */
	protected $config;
	/**
	 * @var FilesystemHelper
	 */
	protected $fs;
	/**
	 * @var Image
	 */
	protected $image;
	/**
	 * @var LoggerInterface
	 */
	protected $logger;
	/**
	 * @var IAvatarManager
	 */
	protected $avatarManager;
	/**
	 * @var IUserManager
	 */
	protected $userManager;
	/**
	 * @var INotificationManager
	 */
	protected $notificationManager;
	/**
	 * @var string
	 */
	protected $dn;
	/**
	 * @var string
	 */
	protected $uid;
	/**
	 * @var string[]
	 */
	protected $refreshedFeatures = [];
	/**
	 * @var string
	 */
	protected $avatarImage;

	/**
	 * DB config keys for user preferences
	 */
	public const USER_PREFKEY_FIRSTLOGIN = 'firstLoginAccomplished';

	/**
	 * @brief constructor, make sure the subclasses call this one!
	 * @param string $username the internal username
	 * @param string $dn the LDAP DN
	 */
	public function __construct($username, $dn, Access $access,
		IConfig $config, FilesystemHelper $fs, Image $image,
		LoggerInterface $logger, IAvatarManager $avatarManager, IUserManager $userManager,
		INotificationManager $notificationManager) {
		if ($username === null) {
			$logger->error("uid for '$dn' must not be null!", ['app' => 'user_ldap']);
			throw new \InvalidArgumentException('uid must not be null!');
		} elseif ($username === '') {
			$logger->error("uid for '$dn' must not be an empty string", ['app' => 'user_ldap']);
			throw new \InvalidArgumentException('uid must not be an empty string!');
		}

		$this->access = $access;
		$this->connection = $access->getConnection();
		$this->config = $config;
		$this->fs = $fs;
		$this->dn = $dn;
		$this->uid = $username;
		$this->image = $image;
		$this->logger = $logger;
		$this->avatarManager = $avatarManager;
		$this->userManager = $userManager;
		$this->notificationManager = $notificationManager;

		\OCP\Util::connectHook('OC_User', 'post_login', $this, 'handlePasswordExpiry');
	}

	/**
	 * marks a user as deleted
	 *
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function markUser() {
		$curValue = $this->config->getUserValue($this->getUsername(), 'user_ldap', 'isDeleted', '0');
		if ($curValue === '1') {
			// the user is already marked, do not write to DB again
			return;
		}
		$this->config->setUserValue($this->getUsername(), 'user_ldap', 'isDeleted', '1');
		$this->config->setUserValue($this->getUsername(), 'user_ldap', 'foundDeleted', (string)time());
	}

	/**
	 * processes results from LDAP for attributes as returned by getAttributesToRead()
	 * @param array $ldapEntry the user entry as retrieved from LDAP
	 */
	public function processAttributes($ldapEntry) {
		//Quota
		$attr = strtolower($this->connection->ldapQuotaAttribute);
		if (isset($ldapEntry[$attr])) {
			$this->updateQuota($ldapEntry[$attr][0]);
		} else {
			if ($this->connection->ldapQuotaDefault !== '') {
				$this->updateQuota();
			}
		}
		unset($attr);

		//displayName
		$displayName = $displayName2 = '';
		$attr = strtolower($this->connection->ldapUserDisplayName);
		if (isset($ldapEntry[$attr])) {
			$displayName = (string)$ldapEntry[$attr][0];
		}
		$attr = strtolower($this->connection->ldapUserDisplayName2);
		if (isset($ldapEntry[$attr])) {
			$displayName2 = (string)$ldapEntry[$attr][0];
		}
		if ($displayName !== '') {
			$this->composeAndStoreDisplayName($displayName, $displayName2);
			$this->access->cacheUserDisplayName(
				$this->getUsername(),
				$displayName,
				$displayName2
			);
		}
		unset($attr);

		//Email
		//email must be stored after displayname, because it would cause a user
		//change event that will trigger fetching the display name again
		$attr = strtolower($this->connection->ldapEmailAttribute);
		if (isset($ldapEntry[$attr])) {
			$this->updateEmail($ldapEntry[$attr][0]);
		}
		unset($attr);

		// LDAP Username, needed for s2s sharing
		if (isset($ldapEntry['uid'])) {
			$this->storeLDAPUserName($ldapEntry['uid'][0]);
		} elseif (isset($ldapEntry['samaccountname'])) {
			$this->storeLDAPUserName($ldapEntry['samaccountname'][0]);
		}

		//homePath
		if (str_starts_with($this->connection->homeFolderNamingRule, 'attr:')) {
			$attr = strtolower(substr($this->connection->homeFolderNamingRule, strlen('attr:')));
			if (isset($ldapEntry[$attr])) {
				$this->access->cacheUserHome(
					$this->getUsername(), $this->getHomePath($ldapEntry[$attr][0]));
			}
		}

		//memberOf groups
		$cacheKey = 'getMemberOf'.$this->getUsername();
		$groups = false;
		if (isset($ldapEntry['memberof'])) {
			$groups = $ldapEntry['memberof'];
		}
		$this->connection->writeToCache($cacheKey, $groups);

		//external storage var
		$attr = strtolower($this->connection->ldapExtStorageHomeAttribute);
		if (isset($ldapEntry[$attr])) {
			$this->updateExtStorageHome($ldapEntry[$attr][0]);
		}
		unset($attr);

		// check for cached profile data
		$username = $this->getUsername(); // buffer variable, to save resource
		$cacheKey = 'getUserProfile-'.$username;
		$profileCached = $this->connection->getFromCache($cacheKey);
		// honoring profile disabled in config.php and check if user profile was refreshed
		if ($this->config->getSystemValueBool('profile.enabled', true) &&
			($profileCached === null) && // no cache or TTL not expired
			!$this->wasRefreshed('profile')) {
			// check current data
			$profileValues = array();
			//User Profile Field - Phone number
			$attr = strtolower($this->connection->ldapAttributePhone);
			if (!empty($attr)) { // attribute configured
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_PHONE]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - website
			$attr = strtolower($this->connection->ldapAttributeWebsite);
			if (isset($ldapEntry[$attr])) {
				$cutPosition = strpos($ldapEntry[$attr][0], " ");
				if ($cutPosition) {
					// drop appended label
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_WEBSITE]
						= substr($ldapEntry[$attr][0], 0, $cutPosition);
				} else {
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_WEBSITE]
						= $ldapEntry[$attr][0];
				}
			} elseif (!empty($attr)) {	// configured, but not defined
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_WEBSITE] = "";
			}
			//User Profile Field - Address
			$attr = strtolower($this->connection->ldapAttributeAddress);
			if (isset($ldapEntry[$attr])) {
				if (str_contains($ldapEntry[$attr][0], '$')) {
					// basic format conversion from postalAddress syntax to commata delimited
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_ADDRESS]
						= str_replace('$', ", ", $ldapEntry[$attr][0]);
				} else {
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_ADDRESS]
						= $ldapEntry[$attr][0];
				}
			} elseif (!empty($attr)) {	// configured, but not defined
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_ADDRESS] = "";
			}
			//User Profile Field - Twitter
			$attr = strtolower($this->connection->ldapAttributeTwitter);
			if (!empty($attr)) {
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_TWITTER]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - fediverse
			$attr = strtolower($this->connection->ldapAttributeFediverse);
			if (!empty($attr)) {
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_FEDIVERSE]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - organisation
			$attr = strtolower($this->connection->ldapAttributeOrganisation);
			if (!empty($attr)) {
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_ORGANISATION]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - role
			$attr = strtolower($this->connection->ldapAttributeRole);
			if (!empty($attr)) {
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_ROLE]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - headline
			$attr = strtolower($this->connection->ldapAttributeHeadline);
			if (!empty($attr)) {
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_HEADLINE]
					= $ldapEntry[$attr][0] ?? "";
			}
			//User Profile Field - biography
			$attr = strtolower($this->connection->ldapAttributeBiography);
			if (isset($ldapEntry[$attr])) {
				if (str_contains($ldapEntry[$attr][0], '\r')) {
					// convert line endings
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_BIOGRAPHY]
						= str_replace(array("\r\n","\r"), "\n", $ldapEntry[$attr][0]);
				} else {
					$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_BIOGRAPHY]
						= $ldapEntry[$attr][0];
				}
			} elseif (!empty($attr)) {	// configured, but not defined
				$profileValues[\OCP\Accounts\IAccountManager::PROPERTY_BIOGRAPHY] = "";
			}
			// check for changed data and cache just for TTL checking
			$checksum = hash('sha256', json_encode($profileValues));
			$this->connection->writeToCache($cacheKey, $checksum // write array to cache. is waste of cache space
				, null); // use ldapCacheTTL from configuration
			// Update user profile
			if ($this->config->getUserValue($username, 'user_ldap', 'lastProfileChecksum', null) !== $checksum) {
				$this->config->setUserValue($username, 'user_ldap', 'lastProfileChecksum', $checksum);
				$this->updateProfile($profileValues);
				$this->logger->info("updated profile uid=$username", ['app' => 'user_ldap']);
			} else {
				$this->logger->debug("profile data from LDAP unchanged", ['app' => 'user_ldap', 'uid' => $username]);
			}
			unset($attr);
		} elseif ($profileCached !== null) { // message delayed, to declutter log
			$this->logger->debug("skipping profile check, while cached data exist", ['app' => 'user_ldap', 'uid' => $username]);
		}

		//Avatar
		/** @var Connection $connection */
		$connection = $this->access->getConnection();
		$attributes = $connection->resolveRule('avatar');
		foreach ($attributes as $attribute) {
			if (isset($ldapEntry[$attribute])) {
				$this->avatarImage = $ldapEntry[$attribute][0];
				// the call to the method that saves the avatar in the file
				// system must be postponed after the login. It is to ensure
				// external mounts are mounted properly (e.g. with login
				// credentials from the session).
				\OCP\Util::connectHook('OC_User', 'post_login', $this, 'updateAvatarPostLogin');
				break;
			}
		}
	}

	/**
	 * @brief returns the LDAP DN of the user
	 * @return string
	 */
	public function getDN() {
		return $this->dn;
	}

	/**
	 * @brief returns the Nextcloud internal username of the user
	 * @return string
	 */
	public function getUsername() {
		return $this->uid;
	}

	/**
	 * returns the home directory of the user if specified by LDAP settings
	 * @param ?string $valueFromLDAP
	 * @return false|string
	 * @throws \Exception
	 */
	public function getHomePath($valueFromLDAP = null) {
		$path = (string)$valueFromLDAP;
		$attr = null;

		if (is_null($valueFromLDAP)
		   && str_starts_with($this->access->connection->homeFolderNamingRule, 'attr:')
		   && $this->access->connection->homeFolderNamingRule !== 'attr:') {
			$attr = substr($this->access->connection->homeFolderNamingRule, strlen('attr:'));
			$homedir = $this->access->readAttribute($this->access->username2dn($this->getUsername()), $attr);
			if ($homedir && isset($homedir[0])) {
				$path = $homedir[0];
			}
		}

		if ($path !== '') {
			//if attribute's value is an absolute path take this, otherwise append it to data dir
			//check for / at the beginning or pattern c:\ resp. c:/
			if ('/' !== $path[0]
			   && !(3 < strlen($path) && ctype_alpha($path[0])
				   && $path[1] === ':' && ('\\' === $path[2] || '/' === $path[2]))
			) {
				$path = $this->config->getSystemValue('datadirectory',
					\OC::$SERVERROOT.'/data') . '/' . $path;
			}
			//we need it to store it in the DB as well in case a user gets
			//deleted so we can clean up afterwards
			$this->config->setUserValue(
				$this->getUsername(), 'user_ldap', 'homePath', $path
			);
			return $path;
		}

		if (!is_null($attr)
			&& $this->config->getAppValue('user_ldap', 'enforce_home_folder_naming_rule', 'true')
		) {
			// a naming rule attribute is defined, but it doesn't exist for that LDAP user
			throw new \Exception('Home dir attribute can\'t be read from LDAP for uid: ' . $this->getUsername());
		}

		//false will apply default behaviour as defined and done by OC_User
		$this->config->setUserValue($this->getUsername(), 'user_ldap', 'homePath', '');
		return false;
	}

	public function getMemberOfGroups() {
		$cacheKey = 'getMemberOf'.$this->getUsername();
		$memberOfGroups = $this->connection->getFromCache($cacheKey);
		if (!is_null($memberOfGroups)) {
			return $memberOfGroups;
		}
		$groupDNs = $this->access->readAttribute($this->getDN(), 'memberOf');
		$this->connection->writeToCache($cacheKey, $groupDNs);
		return $groupDNs;
	}

	/**
	 * @brief reads the image from LDAP that shall be used as Avatar
	 * @return string data (provided by LDAP) | false
	 */
	public function getAvatarImage() {
		if (!is_null($this->avatarImage)) {
			return $this->avatarImage;
		}

		$this->avatarImage = false;
		/** @var Connection $connection */
		$connection = $this->access->getConnection();
		$attributes = $connection->resolveRule('avatar');
		foreach ($attributes as $attribute) {
			$result = $this->access->readAttribute($this->dn, $attribute);
			if ($result !== false && is_array($result) && isset($result[0])) {
				$this->avatarImage = $result[0];
				break;
			}
		}

		return $this->avatarImage;
	}

	/**
	 * @brief marks the user as having logged in at least once
	 * @return null
	 */
	public function markLogin() {
		$this->config->setUserValue(
			$this->uid, 'user_ldap', self::USER_PREFKEY_FIRSTLOGIN, '1');
	}

	/**
	 * Stores a key-value pair in relation to this user
	 *
	 * @param string $key
	 * @param string $value
	 */
	private function store($key, $value) {
		$this->config->setUserValue($this->uid, 'user_ldap', $key, $value);
	}

	/**
	 * Composes the display name and stores it in the database. The final
	 * display name is returned.
	 *
	 * @param string $displayName
	 * @param string $displayName2
	 * @return string the effective display name
	 */
	public function composeAndStoreDisplayName($displayName, $displayName2 = '') {
		$displayName2 = (string)$displayName2;
		if ($displayName2 !== '') {
			$displayName .= ' (' . $displayName2 . ')';
		}
		$oldName = $this->config->getUserValue($this->uid, 'user_ldap', 'displayName', null);
		if ($oldName !== $displayName) {
			$this->store('displayName', $displayName);
			$user = $this->userManager->get($this->getUsername());
			if (!empty($oldName) && $user instanceof \OC\User\User) {
				// if it was empty, it would be a new record, not a change emitting the trigger could
				// potentially cause a UniqueConstraintViolationException, depending on some factors.
				$user->triggerChange('displayName', $displayName, $oldName);
			}
		}
		return $displayName;
	}

	/**
	 * Stores the LDAP Username in the Database
	 * @param string $userName
	 */
	public function storeLDAPUserName($userName) {
		$this->store('uid', $userName);
	}

	/**
	 * @brief checks whether an update method specified by feature was run
	 * already. If not, it will marked like this, because it is expected that
	 * the method will be run, when false is returned.
	 * @param string $feature email | quota | avatar | profile (can be extended)
	 * @return bool
	 */
	private function wasRefreshed($feature) {
		if (isset($this->refreshedFeatures[$feature])) {
			return true;
		}
		$this->refreshedFeatures[$feature] = 1;
		return false;
	}

	/**
	 * fetches the email from LDAP and stores it as Nextcloud user value
	 * @param string $valueFromLDAP if known, to save an LDAP read request
	 * @return null
	 */
	public function updateEmail($valueFromLDAP = null) {
		if ($this->wasRefreshed('email')) {
			return;
		}
		$email = (string)$valueFromLDAP;
		if (is_null($valueFromLDAP)) {
			$emailAttribute = $this->connection->ldapEmailAttribute;
			if ($emailAttribute !== '') {
				$aEmail = $this->access->readAttribute($this->dn, $emailAttribute);
				if (is_array($aEmail) && (count($aEmail) > 0)) {
					$email = (string)$aEmail[0];
				}
			}
		}
		if ($email !== '') {
			$user = $this->userManager->get($this->uid);
			if (!is_null($user)) {
				$currentEmail = (string)$user->getSystemEMailAddress();
				if ($currentEmail !== $email) {
					$user->setEMailAddress($email);
				}
			}
		}
	}

	/**
	 * Overall process goes as follow:
	 * 1. fetch the quota from LDAP and check if it's parseable with the "verifyQuotaValue" function
	 * 2. if the value can't be fetched, is empty or not parseable, use the default LDAP quota
	 * 3. if the default LDAP quota can't be parsed, use the Nextcloud's default quota (use 'default')
	 * 4. check if the target user exists and set the quota for the user.
	 *
	 * In order to improve performance and prevent an unwanted extra LDAP call, the $valueFromLDAP
	 * parameter can be passed with the value of the attribute. This value will be considered as the
	 * quota for the user coming from the LDAP server (step 1 of the process) It can be useful to
	 * fetch all the user's attributes in one call and use the fetched values in this function.
	 * The expected value for that parameter is a string describing the quota for the user. Valid
	 * values are 'none' (unlimited), 'default' (the Nextcloud's default quota), '1234' (quota in
	 * bytes), '1234 MB' (quota in MB - check the \OC_Helper::computerFileSize method for more info)
	 *
	 * fetches the quota from LDAP and stores it as Nextcloud user value
	 * @param ?string $valueFromLDAP the quota attribute's value can be passed,
	 * to save the readAttribute request
	 * @return void
	 */
	public function updateQuota($valueFromLDAP = null) {
		if ($this->wasRefreshed('quota')) {
			return;
		}

		$quotaAttribute = $this->connection->ldapQuotaAttribute;
		$defaultQuota = $this->connection->ldapQuotaDefault;
		if ($quotaAttribute === '' && $defaultQuota === '') {
			return;
		}

		$quota = false;
		if (is_null($valueFromLDAP) && $quotaAttribute !== '') {
			$aQuota = $this->access->readAttribute($this->dn, $quotaAttribute);
			if ($aQuota && (count($aQuota) > 0) && $this->verifyQuotaValue($aQuota[0])) {
				$quota = $aQuota[0];
			} elseif (is_array($aQuota) && isset($aQuota[0])) {
				$this->logger->debug('no suitable LDAP quota found for user ' . $this->uid . ': [' . $aQuota[0] . ']', ['app' => 'user_ldap']);
			}
		} elseif (!is_null($valueFromLDAP) && $this->verifyQuotaValue($valueFromLDAP)) {
			$quota = $valueFromLDAP;
		} else {
			$this->logger->debug('no suitable LDAP quota found for user ' . $this->uid . ': [' . $valueFromLDAP . ']', ['app' => 'user_ldap']);
		}

		if ($quota === false && $this->verifyQuotaValue($defaultQuota)) {
			// quota not found using the LDAP attribute (or not parseable). Try the default quota
			$quota = $defaultQuota;
		} elseif ($quota === false) {
			$this->logger->debug('no suitable default quota found for user ' . $this->uid . ': [' . $defaultQuota . ']', ['app' => 'user_ldap']);
			return;
		}

		$targetUser = $this->userManager->get($this->uid);
		if ($targetUser instanceof IUser) {
			$targetUser->setQuota($quota);
		} else {
			$this->logger->info('trying to set a quota for user ' . $this->uid . ' but the user is missing', ['app' => 'user_ldap']);
		}
	}

	private function verifyQuotaValue(string $quotaValue) {
		return $quotaValue === 'none' || $quotaValue === 'default' || \OC_Helper::computerFileSize($quotaValue) !== false;
	}

	/**
	 * takes values from LDAP and stores it as Nextcloud user profile value
	 *
	 * @param array $profileValues associative array of property keys and values from LDAP
	 */
	private function updateProfile(array $profileValues): void {
		// check if given array is empty
		if (empty($profileValues)) {
			return; // okay, nothing to do
		}
		// fetch/prepare user
		$user = $this->userManager->get($this->uid);
		if (is_null($user)) {
			$this->logger->error('could not get user for uid='.$this->uid.'', ['app' => 'user_ldap']);
			return;
		}
		// prepare AccountManager and Account
		$accountManager = Server::get(IAccountManager::class);
		$account = $accountManager->getAccount($user);	// get Account
		$defaultScopes = array_merge(AccountManager::DEFAULT_SCOPES,
			$this->config->getSystemValue('account_manager.default_property_scope', []));
		// loop through the properties and handle them
		foreach ($profileValues as $property => $valueFromLDAP) {
			// check and update profile properties
			$value = (is_array($valueFromLDAP) ? $valueFromLDAP[0] : $valueFromLDAP); // take ONLY the first value, if multiple values specified
			try {
				$accountProperty = $account->getProperty($property);
				$currentValue = $accountProperty->getValue();
				$scope = ($accountProperty->getScope() ? $accountProperty->getScope()
					: $defaultScopes[$property]);
			} catch (PropertyDoesNotExistException $e) { // thrown at getProperty
				$this->logger->error('property does not exist: '.$property
					.' for uid='.$this->uid.'', ['app' => 'user_ldap', 'exception' => $e]);
				$currentValue = '';
				$scope = $defaultScopes[$property];
			}
			$verified = IAccountManager::VERIFIED; // trust the LDAP admin knew what he put there
			if ($currentValue !== $value) {
				$account->setProperty($property, $value, $scope, $verified);
				$this->logger->debug('update user profile: '.$property.'='.$value
					.' for uid='.$this->uid.'', ['app' => 'user_ldap']);
			}
		}
		try {
			$accountManager->updateAccount($account); // may throw InvalidArgumentException
		} catch (\InvalidArgumentException $e) {
			$this->logger->error('invalid data from LDAP: for uid='.$this->uid.'', ['app' => 'user_ldap', 'func' => 'updateProfile'
				, 'exception' => $e]);
		}
	}

	/**
	 * called by a post_login hook to save the avatar picture
	 *
	 * @param array $params
	 */
	public function updateAvatarPostLogin($params) {
		if (isset($params['uid']) && $params['uid'] === $this->getUsername()) {
			$this->updateAvatar();
		}
	}

	/**
	 * @brief attempts to get an image from LDAP and sets it as Nextcloud avatar
	 * @return bool true when the avatar was set successfully or is up to date
	 */
	public function updateAvatar(bool $force = false): bool {
		if (!$force && $this->wasRefreshed('avatar')) {
			return false;
		}
		$avatarImage = $this->getAvatarImage();
		if ($avatarImage === false) {
			//not set, nothing left to do;
			return false;
		}

		if (!$this->image->loadFromBase64(base64_encode($avatarImage))) {
			return false;
		}

		// use the checksum before modifications
		$checksum = md5($this->image->data());

		if ($checksum === $this->config->getUserValue($this->uid, 'user_ldap', 'lastAvatarChecksum', '') && $this->avatarExists()) {
			return true;
		}

		$isSet = $this->setOwnCloudAvatar();

		if ($isSet) {
			// save checksum only after successful setting
			$this->config->setUserValue($this->uid, 'user_ldap', 'lastAvatarChecksum', $checksum);
		}

		return $isSet;
	}

	private function avatarExists(): bool {
		try {
			$currentAvatar = $this->avatarManager->getAvatar($this->uid);
			return $currentAvatar->exists() && $currentAvatar->isCustomAvatar();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @brief sets an image as Nextcloud avatar
	 * @return bool
	 */
	private function setOwnCloudAvatar() {
		if (!$this->image->valid()) {
			$this->logger->error('avatar image data from LDAP invalid for '.$this->dn, ['app' => 'user_ldap']);
			return false;
		}


		//make sure it is a square and not bigger than 512x512
		$size = min([$this->image->width(), $this->image->height(), 512]);
		if (!$this->image->centerCrop($size)) {
			$this->logger->error('croping image for avatar failed for '.$this->dn, ['app' => 'user_ldap']);
			return false;
		}

		if (!$this->fs->isLoaded()) {
			$this->fs->setup($this->uid);
		}

		try {
			$avatar = $this->avatarManager->getAvatar($this->uid);
			$avatar->set($this->image);
			return true;
		} catch (\Exception $e) {
			$this->logger->info('Could not set avatar for ' . $this->dn, ['exception' => $e]);
		}
		return false;
	}

	/**
	 * @throws AttributeNotSet
	 * @throws \OC\ServerNotAvailableException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function getExtStorageHome():string {
		$value = $this->config->getUserValue($this->getUsername(), 'user_ldap', 'extStorageHome', '');
		if ($value !== '') {
			return $value;
		}

		$value = $this->updateExtStorageHome();
		if ($value !== '') {
			return $value;
		}

		throw new AttributeNotSet(sprintf(
			'external home storage attribute yield no value for %s', $this->getUsername()
		));
	}

	/**
	 * @throws \OCP\PreConditionNotMetException
	 * @throws \OC\ServerNotAvailableException
	 */
	public function updateExtStorageHome(string $valueFromLDAP = null):string {
		if ($valueFromLDAP === null) {
			$extHomeValues = $this->access->readAttribute($this->getDN(), $this->connection->ldapExtStorageHomeAttribute);
		} else {
			$extHomeValues = [$valueFromLDAP];
		}
		if ($extHomeValues && isset($extHomeValues[0])) {
			$extHome = $extHomeValues[0];
			$this->config->setUserValue($this->getUsername(), 'user_ldap', 'extStorageHome', $extHome);
			return $extHome;
		} else {
			$this->config->deleteUserValue($this->getUsername(), 'user_ldap', 'extStorageHome');
			return '';
		}
	}

	/**
	 * called by a post_login hook to handle password expiry
	 *
	 * @param array $params
	 */
	public function handlePasswordExpiry($params) {
		$ppolicyDN = $this->connection->ldapDefaultPPolicyDN;
		if (empty($ppolicyDN) || ((int)$this->connection->turnOnPasswordChange !== 1)) {
			return;//password expiry handling disabled
		}
		$uid = $params['uid'];
		if (isset($uid) && $uid === $this->getUsername()) {
			//retrieve relevant user attributes
			$result = $this->access->search('objectclass=*', $this->dn, ['pwdpolicysubentry', 'pwdgraceusetime', 'pwdreset', 'pwdchangedtime']);

			if (array_key_exists('pwdpolicysubentry', $result[0])) {
				$pwdPolicySubentry = $result[0]['pwdpolicysubentry'];
				if ($pwdPolicySubentry && (count($pwdPolicySubentry) > 0)) {
					$ppolicyDN = $pwdPolicySubentry[0];//custom ppolicy DN
				}
			}

			$pwdGraceUseTime = array_key_exists('pwdgraceusetime', $result[0]) ? $result[0]['pwdgraceusetime'] : [];
			$pwdReset = array_key_exists('pwdreset', $result[0]) ? $result[0]['pwdreset'] : [];
			$pwdChangedTime = array_key_exists('pwdchangedtime', $result[0]) ? $result[0]['pwdchangedtime'] : [];

			//retrieve relevant password policy attributes
			$cacheKey = 'ppolicyAttributes' . $ppolicyDN;
			$result = $this->connection->getFromCache($cacheKey);
			if (is_null($result)) {
				$result = $this->access->search('objectclass=*', $ppolicyDN, ['pwdgraceauthnlimit', 'pwdmaxage', 'pwdexpirewarning']);
				$this->connection->writeToCache($cacheKey, $result);
			}

			$pwdGraceAuthNLimit = array_key_exists('pwdgraceauthnlimit', $result[0]) ? $result[0]['pwdgraceauthnlimit'] : [];
			$pwdMaxAge = array_key_exists('pwdmaxage', $result[0]) ? $result[0]['pwdmaxage'] : [];
			$pwdExpireWarning = array_key_exists('pwdexpirewarning', $result[0]) ? $result[0]['pwdexpirewarning'] : [];

			//handle grace login
			if (!empty($pwdGraceUseTime)) { //was this a grace login?
				if (!empty($pwdGraceAuthNLimit)
					&& count($pwdGraceUseTime) < (int)$pwdGraceAuthNLimit[0]) { //at least one more grace login available?
					$this->config->setUserValue($uid, 'user_ldap', 'needsPasswordReset', 'true');
					header('Location: '.\OC::$server->getURLGenerator()->linkToRouteAbsolute(
						'user_ldap.renewPassword.showRenewPasswordForm', ['user' => $uid]));
				} else { //no more grace login available
					header('Location: '.\OC::$server->getURLGenerator()->linkToRouteAbsolute(
						'user_ldap.renewPassword.showLoginFormInvalidPassword', ['user' => $uid]));
				}
				exit();
			}
			//handle pwdReset attribute
			if (!empty($pwdReset) && $pwdReset[0] === 'TRUE') { //user must change his password
				$this->config->setUserValue($uid, 'user_ldap', 'needsPasswordReset', 'true');
				header('Location: '.\OC::$server->getURLGenerator()->linkToRouteAbsolute(
					'user_ldap.renewPassword.showRenewPasswordForm', ['user' => $uid]));
				exit();
			}
			//handle password expiry warning
			if (!empty($pwdChangedTime)) {
				if (!empty($pwdMaxAge)
					&& !empty($pwdExpireWarning)) {
					$pwdMaxAgeInt = (int)$pwdMaxAge[0];
					$pwdExpireWarningInt = (int)$pwdExpireWarning[0];
					if ($pwdMaxAgeInt > 0 && $pwdExpireWarningInt > 0) {
						$pwdChangedTimeDt = \DateTime::createFromFormat('YmdHisZ', $pwdChangedTime[0]);
						$pwdChangedTimeDt->add(new \DateInterval('PT'.$pwdMaxAgeInt.'S'));
						$currentDateTime = new \DateTime();
						$secondsToExpiry = $pwdChangedTimeDt->getTimestamp() - $currentDateTime->getTimestamp();
						if ($secondsToExpiry <= $pwdExpireWarningInt) {
							//remove last password expiry warning if any
							$notification = $this->notificationManager->createNotification();
							$notification->setApp('user_ldap')
								->setUser($uid)
								->setObject('pwd_exp_warn', $uid)
							;
							$this->notificationManager->markProcessed($notification);
							//create new password expiry warning
							$notification = $this->notificationManager->createNotification();
							$notification->setApp('user_ldap')
								->setUser($uid)
								->setDateTime($currentDateTime)
								->setObject('pwd_exp_warn', $uid)
								->setSubject('pwd_exp_warn_days', [(int) ceil($secondsToExpiry / 60 / 60 / 24)])
							;
							$this->notificationManager->notify($notification);
						}
					}
				}
			}
		}
	}
}
