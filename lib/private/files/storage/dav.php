<?php
/**
 * @author Alexander Bogdanov <syn@li.ru>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Carlos Cerrillo <ccerrillo@gmail.com>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philippe Kueck <pk@plusline.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Files\Storage;

use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;
use Sabre\HTTP\ClientHttpException;

/**
 * Class DAV
 *
 * @package OC\Files\Storage
 */
class DAV extends \OC\Files\Storage\Common {
	protected $password;
	protected $user;
	protected $host;
	protected $secure;
	protected $root;
	protected $certPath;
	protected $ready;
	/**
	 * @var \Sabre\DAV\Client
	 */
	private $client;

	/** @var array */
	private static $tempFiles = [];

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct($params) {
		if (isset($params['host']) && isset($params['user']) && isset($params['password'])) {
			$host = $params['host'];
			//remove leading http[s], will be generated in createBaseUri()
			if (substr($host, 0, 8) == "https://") $host = substr($host, 8);
			else if (substr($host, 0, 7) == "http://") $host = substr($host, 7);
			$this->host = $host;
			$this->user = $params['user'];
			$this->password = $params['password'];
			if (isset($params['secure'])) {
				if (is_string($params['secure'])) {
					$this->secure = ($params['secure'] === 'true');
				} else {
					$this->secure = (bool)$params['secure'];
				}
			} else {
				$this->secure = false;
			}
			if ($this->secure === true) {
				$certPath = \OC_User::getHome(\OC_User::getUser()) . '/files_external/rootcerts.crt';
				if (file_exists($certPath)) {
					$this->certPath = $certPath;
				}
			}
			$this->root = isset($params['root']) ? $params['root'] : '/';
			if (!$this->root || $this->root[0] != '/') {
				$this->root = '/' . $this->root;
			}
			if (substr($this->root, -1, 1) != '/') {
				$this->root .= '/';
			}
		} else {
			throw new \Exception('Invalid webdav storage configuration');
		}
	}

	private function init() {
		if ($this->ready) {
			return;
		}
		$this->ready = true;

		$settings = array(
			'baseUri' => $this->createBaseUri(),
			'userName' => $this->user,
			'password' => $this->password,
		);

		$this->client = new \Sabre\DAV\Client($settings);
		$this->client->setThrowExceptions(true);

		if ($this->secure === true && $this->certPath) {
			$this->client->addTrustedCertificates($this->certPath);
		}
	}

	/** {@inheritdoc} */
	public function getId() {
		return 'webdav::' . $this->user . '@' . $this->host . '/' . $this->root;
	}

	/** {@inheritdoc} */
	public function createBaseUri() {
		$baseUri = 'http';
		if ($this->secure) {
			$baseUri .= 's';
		}
		$baseUri .= '://' . $this->host . $this->root;
		return $baseUri;
	}

	/** {@inheritdoc} */
	public function mkdir($path) {
		$this->init();
		$path = $this->cleanPath($path);
		return $this->simpleResponse('MKCOL', $path, null, 201);
	}

	/** {@inheritdoc} */
	public function rmdir($path) {
		$this->init();
		$path = $this->cleanPath($path) . '/';
		// FIXME: some WebDAV impl return 403 when trying to DELETE
		// a non-empty folder
		return $this->simpleResponse('DELETE', $path, null, 204);
	}

	/** {@inheritdoc} */
	public function opendir($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array(), 1);
			$id = md5('webdav' . $this->root . $path);
			$content = array();
			$files = array_keys($response);
			array_shift($files); //the first entry is the current directory
			foreach ($files as $file) {
				$file = urldecode(basename($file));
				$content[] = $file;
			}
			\OC\Files\Stream\Dir::register($id, $content);
			return opendir('fakedir://' . $id);
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404) {
				return false;
			}
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/** {@inheritdoc} */
	public function filetype($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array('{DAV:}resourcetype'));
			$responseType = array();
			if (isset($response["{DAV:}resourcetype"])) {
				$responseType = $response["{DAV:}resourcetype"]->resourceType;
			}
			return (count($responseType) > 0 and $responseType[0] == "{DAV:}collection") ? 'dir' : 'file';
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404) {
				return false;
			}
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/** {@inheritdoc} */
	public function file_exists($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$this->client->propfind($this->encodePath($path), array('{DAV:}resourcetype'));
			return true; //no 404 exception
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404) {
				return false;
			}
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/** {@inheritdoc} */
	public function unlink($path) {
		$this->init();
		return $this->simpleResponse('DELETE', $path, null, 204);
	}

	/** {@inheritdoc} */
	public function fopen($path, $mode) {
		$this->init();
		$path = $this->cleanPath($path);
		switch ($mode) {
			case 'r':
			case 'rb':
				if (!$this->file_exists($path)) {
					return false;
				}
				//straight up curl instead of sabredav here, sabredav put's the entire get result in memory
				$curl = curl_init();
				$fp = fopen('php://temp', 'r+');
				curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
				curl_setopt($curl, CURLOPT_URL, $this->createBaseUri() . $this->encodePath($path));
				curl_setopt($curl, CURLOPT_FILE, $fp);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
				curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
				if ($this->secure === true) {
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
					if ($this->certPath) {
						curl_setopt($curl, CURLOPT_CAINFO, $this->certPath);
					}
				}

				curl_exec($curl);
				$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ($statusCode !== 200) {
					\OCP\Util::writeLog("webdav client", 'curl GET ' . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . ' returned status code ' . $statusCode, \OCP\Util::ERROR);
				}
				curl_close($curl);
				rewind($fp);
				return $fp;
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				//emulate these
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				if ($this->file_exists($path)) {
					if (!$this->isUpdatable($path)) {
						return false;
					}
					$tmpFile = $this->getCachedFile($path);
				} else {
					if (!$this->isCreatable(dirname($path))) {
						return false;
					}
					$tmpFile = \OCP\Files::tmpFile($ext);
				}
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				self::$tempFiles[$tmpFile] = $path;
				return fopen('close://' . $tmpFile, $mode);
		}
	}

	/**
	 * @param string $tmpFile
	 */
	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
			$this->uploadFile($tmpFile, self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
		}
	}

	/** {@inheritdoc} */
	public function free_space($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array('{DAV:}quota-available-bytes'));
			if (isset($response['{DAV:}quota-available-bytes'])) {
				return (int)$response['{DAV:}quota-available-bytes'];
			} else {
				return \OCP\Files\FileInfo::SPACE_UNKNOWN;
			}
		} catch (\Exception $e) {
			return \OCP\Files\FileInfo::SPACE_UNKNOWN;
		}
	}

	/** {@inheritdoc} */
	public function touch($path, $mtime = null) {
		$this->init();
		if (is_null($mtime)) {
			$mtime = time();
		}
		$path = $this->cleanPath($path);

		// if file exists, update the mtime, else create a new empty file
		if ($this->file_exists($path)) {
			try {
				$this->client->proppatch($this->encodePath($path), array('{DAV:}lastmodified' => $mtime));
			} catch (ClientHttpException $e) {
				if ($e->getHttpStatus() === 501) {
					return false;
				}
				$this->convertSabreException($e);
				return false;
			} catch (\Exception $e) {
				// TODO: log for now, but in the future need to wrap/rethrow exception
				\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		} else {
			$this->file_put_contents($path, '');
		}
		return true;
	}

	/**
	 * @param string $path
	 * @param string $target
	 */
	protected function uploadFile($path, $target) {
		$this->init();
		$source = fopen($path, 'r');

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
		curl_setopt($curl, CURLOPT_URL, $this->createBaseUri() . $this->encodePath($target));
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_INFILE, $source); // file pointer
		curl_setopt($curl, CURLOPT_INFILESIZE, filesize($path));
		curl_setopt($curl, CURLOPT_PUT, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
		curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
		if ($this->secure === true) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			if ($this->certPath) {
				curl_setopt($curl, CURLOPT_CAINFO, $this->certPath);
			}
		}
		curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($statusCode !== 200) {
			\OCP\Util::writeLog("webdav client", 'curl GET ' . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . ' returned status code ' . $statusCode, \OCP\Util::ERROR);
		}
		curl_close($curl);
		fclose($source);
		$this->removeCachedFile($target);
	}

	/** {@inheritdoc} */
	public function rename($path1, $path2) {
		$this->init();
		$path1 = $this->encodePath($this->cleanPath($path1));
		$path2 = $this->createBaseUri() . $this->encodePath($this->cleanPath($path2));
		try {
			$this->client->request('MOVE', $path1, null, array('Destination' => $path2));
			$this->removeCachedFile($path1);
			$this->removeCachedFile($path2);
			return true;
		} catch (ClientHttpException $e) {
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/** {@inheritdoc} */
	public function copy($path1, $path2) {
		$this->init();
		$path1 = $this->encodePath($this->cleanPath($path1));
		$path2 = $this->createBaseUri() . $this->encodePath($this->cleanPath($path2));
		try {
			$this->client->request('COPY', $path1, null, array('Destination' => $path2));
			$this->removeCachedFile($path2);
			return true;
		} catch (ClientHttpException $e) {
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/** {@inheritdoc} */
	public function stat($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array('{DAV:}getlastmodified', '{DAV:}getcontentlength'));
			return array(
				'mtime' => strtotime($response['{DAV:}getlastmodified']),
				'size' => (int)isset($response['{DAV:}getcontentlength']) ? $response['{DAV:}getcontentlength'] : 0,
			);
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404) {
				return array();
			}
			$this->convertSabreException($e);
			return array();
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return array();
		}
	}

	/** {@inheritdoc} */
	public function getMimeType($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array('{DAV:}getcontenttype', '{DAV:}resourcetype'));
			$responseType = array();
			if (isset($response["{DAV:}resourcetype"])) {
				$responseType = $response["{DAV:}resourcetype"]->resourceType;
			}
			$type = (count($responseType) > 0 and $responseType[0] == "{DAV:}collection") ? 'dir' : 'file';
			if ($type == 'dir') {
				return 'httpd/unix-directory';
			} elseif (isset($response['{DAV:}getcontenttype'])) {
				return $response['{DAV:}getcontenttype'];
			} else {
				return false;
			}
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404) {
				return false;
			}
			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function cleanPath($path) {
		if ($path === "") {
			return $path;
		}
		$path = \OC\Files\Filesystem::normalizePath($path);
		// remove leading slash
		return substr($path, 1);
	}

	/**
	 * URL encodes the given path but keeps the slashes
	 *
	 * @param string $path to encode
	 * @return string encoded path
	 */
	private function encodePath($path) {
		// slashes need to stay
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param string|resource|null $body
	 * @param int $expected
	 * @return bool
	 * @throws StorageInvalidException
	 * @throws StorageNotAvailableException
	 */
	private function simpleResponse($method, $path, $body, $expected) {
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->request($method, $this->encodePath($path), $body);
			return $response['statusCode'] == $expected;
		} catch (ClientHttpException $e) {
			if ($e->getHttpStatus() === 404 && $method === 'DELETE') {
				return false;
			}

			$this->convertSabreException($e);
			return false;
		} catch (\Exception $e) {
			// TODO: log for now, but in the future need to wrap/rethrow exception
			\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		return true;
	}

	/** {@inheritdoc} */
	public function isUpdatable($path) {
		return (bool)($this->getPermissions($path) & \OCP\Constants::PERMISSION_UPDATE);
	}

	/** {@inheritdoc} */
	public function isCreatable($path) {
		return (bool)($this->getPermissions($path) & \OCP\Constants::PERMISSION_CREATE);
	}

	/** {@inheritdoc} */
	public function isSharable($path) {
		return (bool)($this->getPermissions($path) & \OCP\Constants::PERMISSION_SHARE);
	}

	/** {@inheritdoc} */
	public function isDeletable($path) {
		return (bool)($this->getPermissions($path) & \OCP\Constants::PERMISSION_DELETE);
	}

	/** {@inheritdoc} */
	public function getPermissions($path) {
		$this->init();
		$path = $this->cleanPath($path);
		$response = $this->client->propfind($this->encodePath($path), array('{http://owncloud.org/ns}permissions'));
		if (isset($response['{http://owncloud.org/ns}permissions'])) {
			return $this->parsePermissions($response['{http://owncloud.org/ns}permissions']);
		} else if ($this->is_dir($path)) {
			return \OCP\Constants::PERMISSION_ALL;
		} else if ($this->file_exists($path)) {
			return \OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_CREATE;
		} else {
			return 0;
		}
	}

	/**
	 * @param string $permissionsString
	 * @return int
	 */
	protected function parsePermissions($permissionsString) {
		$permissions = \OCP\Constants::PERMISSION_READ;
		if (strpos($permissionsString, 'R') !== false) {
			$permissions |= \OCP\Constants::PERMISSION_SHARE;
		}
		if (strpos($permissionsString, 'D') !== false) {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}
		if (strpos($permissionsString, 'W') !== false) {
			$permissions |= \OCP\Constants::PERMISSION_UPDATE;
		}
		if (strpos($permissionsString, 'CK') !== false) {
			$permissions |= \OCP\Constants::PERMISSION_CREATE;
			$permissions |= \OCP\Constants::PERMISSION_UPDATE;
		}
		return $permissions;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propfind($this->encodePath($path), array(
				'{DAV:}getlastmodified',
				'{DAV:}getetag',
				'{http://owncloud.org/ns}permissions'
			));
			if (isset($response['{DAV:}getetag'])) {
				$cachedData = $this->getCache()->get($path);
				$etag = trim($response['{DAV:}getetag'], '"');
				if ($cachedData['etag'] !== $etag) {
					return true;
				} else if (isset($response['{http://owncloud.org/ns}permissions'])) {
					$permissions = $this->parsePermissions($response['{http://owncloud.org/ns}permissions']);
					return $permissions !== $cachedData['permissions'];
				} else {
					return false;
				}
			} else {
				$remoteMtime = strtotime($response['{DAV:}getlastmodified']);
				return $remoteMtime > $time;
			}
		} catch (\Exception $e) {
			if ($e->getHttpStatus() === 404) {
				return false;
			}
			$this->convertSabreException($e);
			return false;
		}
	}

	/**
	 * Convert sabre DAV exception to a storage exception,
	 * then throw it
	 *
	 * @param ClientHttpException $e sabre exception
	 * @throws StorageInvalidException if the storage is invalid, for example
	 * when the authentication expired or is invalid
	 * @throws StorageNotAvailableException if the storage is not available,
	 * which might be temporary
	 */
	private function convertSabreException(ClientHttpException $e) {
		\OCP\Util::writeLog('files_external', $e->getMessage(), \OCP\Util::ERROR);
		if ($e->getHttpStatus() === 401) {
			// either password was changed or was invalid all along
			throw new StorageInvalidException(get_class($e).': '.$e->getMessage());
		} else if ($e->getHttpStatus() === 405) {
			// ignore exception for MethodNotAllowed, false will be returned
			return;
		}

		throw new StorageNotAvailableException(get_class($e).': '.$e->getMessage());
	}
}

