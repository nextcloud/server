<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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

/**
* Class to configure the config/mount.php and data/$user/mount.php files
*/
class OC_Mount_Config {

	const MOUNT_TYPE_GLOBAL = 'global';
	const MOUNT_TYPE_GROUP = 'group';
	const MOUNT_TYPE_USER = 'user';

	/**
	* Get details on each of the external storage backends, used for the mount config UI
	* If a custom UI is needed, add the key 'custom' and a javascript file with that name will be loaded
	* If the configuration parameter should be secret, add a '*' to the beginning of the value
	* If the configuration parameter is a boolean, add a '!' to the beginning of the value
	* If the configuration parameter is optional, add a '&' to the beginning of the value
	* If the configuration parameter is hidden, add a '#' to the begining of the value
	* @return array
	*/
	public static function getBackends() {

		$backends['\OC\Files\Storage\Local']=array(
				'backend' => 'Local',
				'configuration' => array(
					'datadir' => 'Location'));

		$backends['\OC\Files\Storage\AmazonS3']=array(
			'backend' => 'Amazon S3',
			'configuration' => array(
				'key' => 'Key',
				'secret' => '*Secret',
				'bucket' => 'Bucket'));

		$backends['\OC\Files\Storage\Dropbox']=array(
			'backend' => 'Dropbox',
			'configuration' => array(
				'configured' => '#configured',
				'app_key' => 'App key',
				'app_secret' => 'App secret',
				'token' => '#token',
				'token_secret' => '#token_secret'),
				'custom' => 'dropbox');

		if(OC_Mount_Config::checkphpftp()) $backends['\OC\Files\Storage\FTP']=array(
			'backend' => 'FTP',
			'configuration' => array(
				'host' => 'URL',
				'user' => 'Username',
				'password' => '*Password',
				'root' => '&Root',
				'secure' => '!Secure ftps://'));

		$backends['\OC\Files\Storage\Google']=array(
			'backend' => 'Google Drive',
			'configuration' => array(
				'configured' => '#configured',
				'token' => '#token',
				'token_secret' => '#token secret'),
				'custom' => 'google');

		$backends['\OC\Files\Storage\SWIFT']=array(
			'backend' => 'OpenStack Swift',
			'configuration' => array(
				'host' => 'URL',
				'user' => 'Username',
				'token' => '*Token',
				'root' => '&Root',
				'secure' => '!Secure ftps://'));

		if(OC_Mount_Config::checksmbclient()) $backends['\OC\Files\Storage\SMB']=array(
			'backend' => 'SMB / CIFS',
			'configuration' => array(
				'host' => 'URL',
				'user' => 'Username',
				'password' => '*Password',
				'share' => 'Share',
				'root' => '&Root'));

		$backends['\OC\Files\Storage\DAV']=array(
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

		return($backends);
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
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);
					// Merge the mount point into the current mount points
					if (isset($system[$mountPoint]) && $system[$mountPoint]['configuration'] == $mount['options']) {
						$system[$mountPoint]['applicable']['groups']
							= array_merge($system[$mountPoint]['applicable']['groups'], array($group));
					} else {
						$system[$mountPoint] = array(
							'class' => $mount['class'],
							'backend' => $backends[$mount['class']]['backend'],
							'configuration' => $mount['options'],
							'applicable' => array('groups' => array($group), 'users' => array()),
							'status' => self::getBackendStatus($mount['class'], $mount['options'])
						);
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
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);
					// Merge the mount point into the current mount points
					if (isset($system[$mountPoint]) && $system[$mountPoint]['configuration'] == $mount['options']) {
						$system[$mountPoint]['applicable']['users']
							= array_merge($system[$mountPoint]['applicable']['users'], array($user));
					} else {
						$system[$mountPoint] = array(
							'class' => $mount['class'],
							'backend' => $backends[$mount['class']]['backend'],
							'configuration' => $mount['options'],
							'applicable' => array('groups' => array(), 'users' => array($user)),
							'status' => self::getBackendStatus($mount['class'], $mount['options'])
						);
					}
				}
			}
		}
		return $system;
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
				// Remove '/uid/files/' from mount point
				$personal[substr($mountPoint, strlen($uid) + 8)] = array(
					'class' => $mount['class'],
					'backend' => $backends[$mount['class']]['backend'],
					'configuration' => $mount['options'],
					'status' => self::getBackendStatus($mount['class'], $mount['options'])
				);
			}
		}
		return $personal;
	}

	private static function getBackendStatus($class, $options) {
		foreach ($options as &$option) {
			$option = str_replace('$user', OCP\User::getUser(), $option);
		}
		if (class_exists($class)) {
			try {
				$storage = new $class($options);
				return $storage->test();
			} catch (Exception $exception) {
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
		if ($isPersonal) {
			// Verify that the mount point applies for the current user
			// Prevent non-admin users from mounting local storage
			if ($applicable != OCP\User::getUser() || $class == '\OC\Files\Storage\Local') {
				return false;
			}
			$mountPoint = '/'.$applicable.'/files/'.ltrim($mountPoint, '/');
		} else {
			$mountPoint = '/$user/files/'.ltrim($mountPoint, '/');
		}
		$mount = array($applicable => array($mountPoint => array('class' => $class, 'options' => $classOptions)));
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
	}

	/**
	 * Returns all user uploaded ssl root certificates
	 * @return array
	 */
	public static function getCertificates() {
		$view = \OCP\Files::getStorage('files_external');
		$path=\OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("").'uploads/';
		\OCP\Util::writeLog('files_external', 'checking path '.$path, \OCP\Util::INFO);
		if ( ! is_dir($path)) {
			//path might not exist (e.g. non-standard OC_User::getHome() value)
			//in this case create full path using 3rd (recursive=true) parameter.
			mkdir($path, 0777, true);
		}
		$result = array();
		$handle = opendir($path);
		if ( ! $handle) {
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
		$view = \OCP\Files::getStorage("files_external");
		$path = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("");

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
			return (empty($output)?false:true);
		}else{
			return(false);
		}
	}

	/**
	 * check if php-ftp is installed
	 */
	public static function checkphpftp() {
		if(function_exists('ftp_login')) {
			return(true);
		}else{
			return(false);
		}
	}

	/**
	 * check dependencies
	 */
	public static function checkDependencies() {
		$l= new OC_L10N('files_external');
		$txt='';
		if(!OC_Mount_Config::checksmbclient()) {
			$txt.=$l->t('<b>Warning:</b> "smbclient" is not installed. Mounting of CIFS/SMB shares is not possible. Please ask your system administrator to install it.').'<br />';
		}
		if(!OC_Mount_Config::checkphpftp()) {
			$txt.=$l->t('<b>Warning:</b> The FTP support in PHP is not enabled or installed. Mounting of FTP shares is not possible. Please ask your system administrator to install it.').'<br />';
		}

		return($txt);
	}
}
