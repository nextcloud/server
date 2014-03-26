<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

set_include_path(
	get_include_path() . PATH_SEPARATOR .
	\OC_App::getAppPath('files_external') . '/3rdparty/phpseclib/phpseclib'
);

/**
 * Class to configure mount.json globally and for users
 */
class OC_Mount_Config {
	// TODO: make this class non-static and give it a proper namespace

	const MOUNT_TYPE_GLOBAL = 'global';
	const MOUNT_TYPE_GROUP = 'group';
	const MOUNT_TYPE_USER = 'user';

	// whether to skip backend test (for unit tests, as this static class is not mockable)
	public static $skipTest = false;

	/**
	* Get details on each of the external storage backends, used for the mount config UI
	* If a custom UI is needed, add the key 'custom' and a javascript file with that name will be loaded
	* If the configuration parameter should be secret, add a '*' to the beginning of the value
	* If the configuration parameter is a boolean, add a '!' to the beginning of the value
	* If the configuration parameter is optional, add a '&' to the beginning of the value
	* If the configuration parameter is hidden, add a '#' to the beginning of the value
	* @return array
	*/
	public static function getBackends() {

		// FIXME: do not rely on php key order for the options order in the UI
		$backends['\OC\Files\Storage\Local']=array(
				'backend' => 'Local',
				'configuration' => array(
					'datadir' => 'Location'));

		$backends['\OC\Files\Storage\AmazonS3']=array(
			'backend' => 'Amazon S3',
			'configuration' => array(
				'key' => 'Access Key',
				'secret' => '*Secret Key',
				'bucket' => 'Bucket',
				'hostname' => '&Hostname (optional)',
				'port' => '&Port (optional)',
				'region' => '&Region (optional)',
				'use_ssl' => '!Enable SSL',
				'use_path_style' => '!Enable Path Style'));

		$backends['\OC\Files\Storage\Dropbox']=array(
			'backend' => 'Dropbox',
			'configuration' => array(
				'configured' => '#configured',
				'app_key' => 'App key',
				'app_secret' => '*App secret',
				'token' => '#token',
				'token_secret' => '#token_secret'),
				'custom' => 'dropbox');

		if(OC_Mount_Config::checkphpftp()) $backends['\OC\Files\Storage\FTP']=array(
			'backend' => 'FTP',
			'configuration' => array(
				'host' => 'Hostname',
				'user' => 'Username',
				'password' => '*Password',
				'root' => '&Root',
				'secure' => '!Secure ftps://'));

		if(OC_Mount_Config::checkcurl()) $backends['\OC\Files\Storage\Google']=array(
			'backend' => 'Google Drive',
			'configuration' => array(
				'configured' => '#configured',
				'client_id' => 'Client ID',
				'client_secret' => '*Client secret',
				'token' => '#token'),
				'custom' => 'google');

		if(OC_Mount_Config::checkcurl()) {
			$backends['\OC\Files\Storage\Swift'] = array(
				'backend' => 'OpenStack Object Storage',
				'configuration' => array(
					'user' => 'Username (required)',
					'bucket' => 'Bucket (required)',
					'region' => '&Region (optional for OpenStack Object Storage)',
					'key' => '*API Key (required for Rackspace Cloud Files)',
					'tenant' => '&Tenantname (required for OpenStack Object Storage)',
					'password' => '*Password (required for OpenStack Object Storage)',
					'service_name' => '&Service Name (required for OpenStack Object Storage)',
					'url' => '&URL of identity endpoint (required for OpenStack Object Storage)',
					'timeout' => '&Timeout of HTTP requests in seconds (optional)',
				)
			);
                }

		if (!OC_Util::runningOnWindows()) {
			if (OC_Mount_Config::checksmbclient()) {
				$backends['\OC\Files\Storage\SMB'] = array(
					'backend' => 'SMB / CIFS',
					'configuration' => array(
						'host' => 'URL',
						'user' => 'Username',
						'password' => '*Password',
						'share' => 'Share',
						'root' => '&Root'));
			}
		}

		if(OC_Mount_Config::checkcurl()) $backends['\OC\Files\Storage\DAV']=array(
			'backend' => 'ownCloud / WebDAV',
			'configuration' => array(
				'host' => 'URL',
				'user' => 'Username',
				'password' => '*Password',
				'root' => '&Root',
				'secure' => '!Secure https://'));

		$backends['\OC\Files\Storage\SFTP']=array(
			'backend' => 'SFTP',
			'configuration' => array(
				'host' => 'URL',
				'user' => 'Username',
				'password' => '*Password',
				'root' => '&Root'));

		$backends['\OC\Files\Storage\iRODS']=array(
			'backend' => 'iRODS',
			'configuration' => array(
				'host' => 'Host',
				'port' => 'Port',
				'use_logon_credentials' => '!Use ownCloud login',
				'user' => 'Username',
				'password' => '*Password',
				'auth_mode' => 'Authentication Mode',
				'zone' => 'Zone'));

		return($backends);
	}

	/**
	 * Hook that mounts the given user's visible mount points
	 * @param array $data
	 */
	public static function initMountPointsHook($data) {
		$mountPoints = self::getAbsoluteMountPoints($data['user']);
		foreach ($mountPoints as $mountPoint => $options) {
			\OC\Files\Filesystem::mount($options['class'], $options['options'], $mountPoint);
		}
	}

	/**
	 * Returns the mount points for the given user.
	 * The mount point is relative to the data directory.
	 *
	 * @param string $user user
	 * @return array of mount point string as key, mountpoint config as value
	 */
	public static function getAbsoluteMountPoints($user) {
		$mountPoints = array();	

		$datadir = \OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data");
		$mount_file = \OC_Config::getValue("mount_file", $datadir . "/mount.json");

		//move config file to it's new position
		if (is_file(\OC::$SERVERROOT . '/config/mount.json')) {
			rename(\OC::$SERVERROOT . '/config/mount.json', $mount_file);
		}

		// Load system mount points
		$mountConfig = self::readData(false);
		if (isset($mountConfig[self::MOUNT_TYPE_GLOBAL])) {
			foreach ($mountConfig[self::MOUNT_TYPE_GLOBAL] as $mountPoint => $options) {
				$options['options'] = self::decryptPasswords($options['options']);
				$mountPoints[$mountPoint] = $options;
			}
		}
		if (isset($mountConfig[self::MOUNT_TYPE_GROUP])) {
			foreach ($mountConfig[self::MOUNT_TYPE_GROUP] as $group => $mounts) {
				if (\OC_Group::inGroup($user, $group)) {
					foreach ($mounts as $mountPoint => $options) {
						$mountPoint = self::setUserVars($user, $mountPoint);
						foreach ($options as &$option) {
							$option = self::setUserVars($user, $option);
						}
						$options['options'] = self::decryptPasswords($options['options']);
						$mountPoints[$mountPoint] = $options;
					}
				}
			}
		}
		if (isset($mountConfig[self::MOUNT_TYPE_USER])) {
			foreach ($mountConfig[self::MOUNT_TYPE_USER] as $mountUser => $mounts) {
				if ($mountUser === 'all' or strtolower($mountUser) === strtolower($user)) {
					foreach ($mounts as $mountPoint => $options) {
						$mountPoint = self::setUserVars($user, $mountPoint);
						foreach ($options as &$option) {
							$option = self::setUserVars($user, $option);
						}
						$options['options'] = self::decryptPasswords($options['options']);
						$mountPoints[$mountPoint] = $options;
					}
				}
			}
		}

		// Load personal mount points
		$mountConfig = self::readData(true);
		if (isset($mountConfig[self::MOUNT_TYPE_USER][$user])) {
			foreach ($mountConfig[self::MOUNT_TYPE_USER][$user] as $mountPoint => $options) {
				$options['options'] = self::decryptPasswords($options['options']);
				$mountPoints[$mountPoint] = $options;
			}
		}

		return $mountPoints;
	}

	/**
	 * fill in the correct values for $user
	 *
	 * @param string $user
	 * @param string $input
	 * @return string
	 */
	private static function setUserVars($user, $input) {
		return str_replace('$user', $user, $input);
	}


	/**
	* Get details on each of the external storage backends, used for the mount config UI
	* Some backends are not available as a personal backend, f.e. Local and such that have
	* been disabled by the admin.
	*
	* If a custom UI is needed, add the key 'custom' and a javascript file with that name will be loaded
	* If the configuration parameter should be secret, add a '*' to the beginning of the value
	* If the configuration parameter is a boolean, add a '!' to the beginning of the value
	* If the configuration parameter is optional, add a '&' to the beginning of the value
	* If the configuration parameter is hidden, add a '#' to the beginning of the value
	* @return array
	*/
	public static function getPersonalBackends() {

		$backends = self::getBackends();

		// Remove local storage and other disabled storages
		unset($backends['\OC\Files\Storage\Local']);

		$allowed_backends = explode(',', OCP\Config::getAppValue('files_external', 'user_mounting_backends', ''));
		foreach ($backends as $backend => $null) {
			if (!in_array($backend, $allowed_backends)) {
				unset($backends[$backend]);
			}
		}

		return $backends;
	}

	/**
	* Get the system mount points
	* The returned array is not in the same format as getUserMountPoints()
	* @return array
	*/
	public static function getSystemMountPoints() {
		$mountPoints = self::readData(false);
		$backends = self::getBackends();
		$system = array();
		if (isset($mountPoints[self::MOUNT_TYPE_GROUP])) {
			foreach ($mountPoints[self::MOUNT_TYPE_GROUP] as $group => $mounts) {
				foreach ($mounts as $mountPoint => $mount) {
					// Update old classes to new namespace
					if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
						$mount['class'] = '\OC\Files\Storage\\'.substr($mount['class'], 15);
					}
					$mount['options'] = self::decryptPasswords($mount['options']);
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);

					$config = array(
						'class' => $mount['class'],
						'mountpoint' => $mountPoint,
						'backend' => $backends[$mount['class']]['backend'],
						'options' => $mount['options'],
						'applicable' => array('groups' => array($group), 'users' => array()),
						'status' => self::getBackendStatus($mount['class'], $mount['options'])
					);
					$hash = self::makeConfigHash($config);
					// If an existing config exists (with same class, mountpoint and options)
					if (isset($system[$hash])) {
						// add the groups into that config
						$system[$hash]['applicable']['groups']
							= array_merge($system[$hash]['applicable']['groups'], array($group));
					} else {
						$system[$hash] = $config;
					}
				}
			}
		}
		if (isset($mountPoints[self::MOUNT_TYPE_USER])) {
			foreach ($mountPoints[self::MOUNT_TYPE_USER] as $user => $mounts) {
				foreach ($mounts as $mountPoint => $mount) {
					// Update old classes to new namespace
					if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
						$mount['class'] = '\OC\Files\Storage\\'.substr($mount['class'], 15);
					}
					$mount['options'] = self::decryptPasswords($mount['options']);
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);
					$config = array(
						'class' => $mount['class'],
						'mountpoint' => $mountPoint,
						'backend' => $backends[$mount['class']]['backend'],
						'options' => $mount['options'],
						'applicable' => array('groups' => array(), 'users' => array($user)),
						'status' => self::getBackendStatus($mount['class'], $mount['options'])
					);
					$hash = self::makeConfigHash($config);
					// If an existing config exists (with same class, mountpoint and options)
					if (isset($system[$hash])) {
						// add the users into that config
						$system[$hash]['applicable']['users']
							= array_merge($system[$hash]['applicable']['users'], array($user));
					} else {
						$system[$hash] = $config;
					}
				}
			}
		}
		return array_values($system);
	}

	/**
	* Get the personal mount points of the current user
	* The returned array is not in the same format as getUserMountPoints()
	* @return array
	*/
	public static function getPersonalMountPoints() {
		$mountPoints = self::readData(true);
		$backends = self::getBackends();
		$uid = OCP\User::getUser();
		$personal = array();
		if (isset($mountPoints[self::MOUNT_TYPE_USER][$uid])) {
			foreach ($mountPoints[self::MOUNT_TYPE_USER][$uid] as $mountPoint => $mount) {
				// Update old classes to new namespace
				if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
					$mount['class'] = '\OC\Files\Storage\\'.substr($mount['class'], 15);
				}
				$mount['options'] = self::decryptPasswords($mount['options']);
				$personal[] = array(
					'class' => $mount['class'],
					// Remove '/uid/files/' from mount point
					'mountpoint' => substr($mountPoint, strlen($uid) + 8),
					'backend' => $backends[$mount['class']]['backend'],
					'options' => $mount['options'],
					'status' => self::getBackendStatus($mount['class'], $mount['options'])
				);
			}
		}
		return $personal;
	}

	/**
	 * Test connecting using the given backend configuration
	 * @param string $class backend class name
	 * @param array $options backend configuration options
	 * @return bool true if the connection succeeded, false otherwise
	 */
	private static function getBackendStatus($class, $options) {
		if (self::$skipTest) {
			return true;
		}
		foreach ($options as &$option) {
			$option = self::setUserVars(OCP\User::getUser(), $option);
		}
		if (class_exists($class)) {
			try {
				$storage = new $class($options);
				return $storage->test();
			} catch (Exception $exception) {
				\OCP\Util::logException('files_external', $exception);
				return false;
			}
		}
		return false;
	}

	/**
	* Add a mount point to the filesystem
	* @param string Mount point
	* @param string Backend class
	* @param array Backend parameters for the class
	* @param string MOUNT_TYPE_GROUP | MOUNT_TYPE_USER
	* @param string User or group to apply mount to
	* @param bool Personal or system mount point i.e. is this being called from the personal or admin page
	* @return bool
	*/
	public static function addMountPoint($mountPoint,
										 $class,
										 $classOptions,
										 $mountType,
										 $applicable,
										 $isPersonal = false) {
		$backends = self::getBackends();
		$mountPoint = OC\Files\Filesystem::normalizePath($mountPoint);
		if ($mountPoint === '' || $mountPoint === '/' || $mountPoint == '/Shared') {
			// can't mount at root or "Shared" folder
			return false;
		}

		if (!isset($backends[$class])) {
			// invalid backend
			return false;
		}	
		if ($isPersonal) {
			// Verify that the mount point applies for the current user
			// Prevent non-admin users from mounting local storage
			if ($applicable !== OCP\User::getUser() || strtolower($class) === '\oc\files\storage\local') {
				return false;
			}
			$mountPoint = '/'.$applicable.'/files/'.ltrim($mountPoint, '/');
		} else {
			$mountPoint = '/$user/files/'.ltrim($mountPoint, '/');
		}

		$mount = array($applicable => array(
			$mountPoint => array(
				'class' => $class,
				'options' => self::encryptPasswords($classOptions))
			)
		);
		$mountPoints = self::readData($isPersonal);
		// Merge the new mount point into the current mount points
		if (isset($mountPoints[$mountType])) {
			if (isset($mountPoints[$mountType][$applicable])) {
				$mountPoints[$mountType][$applicable]
					= array_merge($mountPoints[$mountType][$applicable], $mount[$applicable]);
			} else {
				$mountPoints[$mountType] = array_merge($mountPoints[$mountType], $mount);
			}
		} else {
			$mountPoints[$mountType] = $mount;
		}
		self::writeData($isPersonal, $mountPoints);
		return self::getBackendStatus($class, $classOptions);
	}

	/**
	*
	* @param string Mount point
	* @param string MOUNT_TYPE_GROUP | MOUNT_TYPE_USER
	* @param string User or group to remove mount from
	* @param bool Personal or system mount point
	* @return bool
	*/
	public static function removeMountPoint($mountPoint, $mountType, $applicable, $isPersonal = false) {
		// Verify that the mount point applies for the current user
		if ($isPersonal) {
			if ($applicable != OCP\User::getUser()) {
				return false;
			}
			$mountPoint = '/'.$applicable.'/files/'.ltrim($mountPoint, '/');
		} else {
			$mountPoint = '/$user/files/'.ltrim($mountPoint, '/');
		}
		$mountPoints = self::readData($isPersonal);
		// Remove mount point
		unset($mountPoints[$mountType][$applicable][$mountPoint]);
		// Unset parent arrays if empty
		if (empty($mountPoints[$mountType][$applicable])) {
			unset($mountPoints[$mountType][$applicable]);
			if (empty($mountPoints[$mountType])) {
				unset($mountPoints[$mountType]);
			}
		}
		self::writeData($isPersonal, $mountPoints);
		return true;
	}

	/**
	* Read the mount points in the config file into an array
	* @param bool Personal or system config file
	* @return array
	*/
	private static function readData($isPersonal) {
		$parser = new \OC\ArrayParser();
		if ($isPersonal) {
			$phpFile = OC_User::getHome(OCP\User::getUser()).'/mount.php';
			$jsonFile = OC_User::getHome(OCP\User::getUser()).'/mount.json';
		} else {
			$datadir = \OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data");
			$phpFile = OC::$SERVERROOT.'/config/mount.php';
			$jsonFile = $datadir . '/mount.json';
		}
		if (is_file($jsonFile)) {
			$mountPoints = json_decode(file_get_contents($jsonFile), true);
			if (is_array($mountPoints)) {
				return $mountPoints;
			}
		} elseif (is_file($phpFile)) {
			$mountPoints = $parser->parsePHP(file_get_contents($phpFile));
			if (is_array($mountPoints)) {
				return $mountPoints;
			}
		}
		return array();
	}

	/**
	* Write the mount points to the config file
	* @param bool Personal or system config file
	* @param array Mount points
	*/
	private static function writeData($isPersonal, $data) {
		if ($isPersonal) {
			$file = OC_User::getHome(OCP\User::getUser()).'/mount.json';
		} else {
			$datadir = \OC_Config::getValue("datadirectory", \OC::$SERVERROOT . "/data");
			$file = $datadir . '/mount.json';
		}
		$content = json_encode($data);
		@file_put_contents($file, $content);
		@chmod($file, 0640);
	}

	/**
	 * Returns all user uploaded ssl root certificates
	 * @return array
	 */
	public static function getCertificates() {
		$path=OC_User::getHome(OC_User::getUser()) . '/files_external/uploads/';
		\OCP\Util::writeLog('files_external', 'checking path '.$path, \OCP\Util::INFO);
		if ( ! is_dir($path)) {
			//path might not exist (e.g. non-standard OC_User::getHome() value)
			//in this case create full path using 3rd (recursive=true) parameter.
			mkdir($path, 0777, true);
		}
		$result = array();
		$handle = opendir($path);
		if(!is_resource($handle)) {
			return array();
		}
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') $result[] = $file;
		}
		return $result;
	}

	/**
	 * creates certificate bundle
	 */
	public static function createCertificateBundle() {
		$path=OC_User::getHome(OC_User::getUser()) . '/files_external';

		$certs = OC_Mount_Config::getCertificates();
		$fh_certs = fopen($path."/rootcerts.crt", 'w');
		foreach ($certs as $cert) {
			$file=$path.'/uploads/'.$cert;
			$fh = fopen($file, "r");
			$data = fread($fh, filesize($file));
			fclose($fh);
			if (strpos($data, 'BEGIN CERTIFICATE')) {
				fwrite($fh_certs, $data);
				fwrite($fh_certs, "\r\n");
			}
		}

		fclose($fh_certs);

		return true;
	}

	/**
	 * check if smbclient is installed
	 */
	public static function checksmbclient() {
		if(function_exists('shell_exec')) {
			$output=shell_exec('which smbclient');
			return !empty($output);
		}else{
			return false;
		}
	}

	/**
	 * check if php-ftp is installed
	 */
	public static function checkphpftp() {
		if(function_exists('ftp_login')) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * check if curl is installed
	 */
	public static function checkcurl() {
		return function_exists('curl_init');
	}

	/**
	 * check dependencies
	 */
	public static function checkDependencies() {
		$l= new OC_L10N('files_external');
		$txt='';
		if (!OC_Util::runningOnWindows()) {
			if(!OC_Mount_Config::checksmbclient()) {
				$txt.=$l->t('<b>Warning:</b> "smbclient" is not installed. Mounting of CIFS/SMB shares is not possible. Please ask your system administrator to install it.').'<br />';
			}
		}
		if(!OC_Mount_Config::checkphpftp()) {
			$txt.=$l->t('<b>Warning:</b> The FTP support in PHP is not enabled or installed. Mounting of FTP shares is not possible. Please ask your system administrator to install it.').'<br />';
		}
		if(!OC_Mount_Config::checkcurl()) {
			$txt.=$l->t('<b>Warning:</b> The Curl support in PHP is not enabled or installed. Mounting of ownCloud / WebDAV or GoogleDrive is not possible. Please ask your system administrator to install it.').'<br />';
		}

		return $txt;
	}

	/**
	 * Encrypt passwords in the given config options
	 * @param array $options mount options
	 * @return array updated options
	 */
	private static function encryptPasswords($options) {
		if (isset($options['password'])) {
			$options['password_encrypted'] = self::encryptPassword($options['password']);
			// do not unset the password, we want to keep the keys order
			// on load... because that's how the UI currently works
			$options['password'] = '';
		}
		return $options;
	}

	/**
	 * Decrypt passwords in the given config options
	 * @param array $options mount options
	 * @return array updated options
	 */
	private static function decryptPasswords($options) {
		// note: legacy options might still have the unencrypted password in the "password" field
		if (isset($options['password_encrypted'])) {
			$options['password'] = self::decryptPassword($options['password_encrypted']);
			unset($options['password_encrypted']);
		}
		return $options;
	}

	/**
	 * Encrypt a single password
	 * @param string $password plain text password
	 * @return encrypted password
	 */
	private static function encryptPassword($password) {
		$cipher = self::getCipher();
		$iv = \OCP\Util::generateRandomBytes(16);
		$cipher->setIV($iv);
		return base64_encode($iv . $cipher->encrypt($password));
	}

	/**
	 * Decrypts a single password
	 * @param string $encryptedPassword encrypted password
	 * @return plain text password
	 */
	private static function decryptPassword($encryptedPassword) {
		$cipher = self::getCipher();
		$binaryPassword = base64_decode($encryptedPassword);
		$iv = substr($binaryPassword, 0, 16);
		$cipher->setIV($iv);
		$binaryPassword = substr($binaryPassword, 16);
		return $cipher->decrypt($binaryPassword);
	}

	/**
	 * Returns the encryption cipher
	 */
	private static function getCipher() {
		if (!class_exists('Crypt_AES', false)) {
			include('Crypt/AES.php');
		}
		$cipher = new Crypt_AES(CRYPT_AES_MODE_CBC);
		$cipher->setKey(\OCP\Config::getSystemValue('passwordsalt'));
		return $cipher;
	}

	/**
	 * Computes a hash based on the given configuration.
	 * This is mostly used to find out whether configurations
	 * are the same.
	 */
	private static function makeConfigHash($config) {
		$data = json_encode(
			array(
				'c' => $config['class'],
				'm' => $config['mountpoint'],
				'o' => $config['options']
			)
		);
		return hash('md5', $data);
	}
}
