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

namespace OC\Files\Storage;

set_include_path(get_include_path().PATH_SEPARATOR.
	\OC_App::getAppPath('files_external').'/3rdparty/google-api-php-client/src');
require_once 'Google/Client.php';
require_once 'Google/Service/Drive.php';

class Google extends \OC\Files\Storage\Common {

	private $client;
	private $id;
	private $service;
	private $driveFiles;

	private static $tempFiles = array();

	// Google Doc mimetypes
	const FOLDER = 'application/vnd.google-apps.folder';
	const DOCUMENT = 'application/vnd.google-apps.document';
	const SPREADSHEET = 'application/vnd.google-apps.spreadsheet';
	const DRAWING = 'application/vnd.google-apps.drawing';
	const PRESENTATION = 'application/vnd.google-apps.presentation';

	public function __construct($params) {
		if (isset($params['configured']) && $params['configured'] === 'true'
			&& isset($params['client_id']) && isset($params['client_secret'])
			&& isset($params['token'])
		) {
			$this->client = new \Google_Client();
			$this->client->setClientId($params['client_id']);
			$this->client->setClientSecret($params['client_secret']);
			$this->client->setScopes(array('https://www.googleapis.com/auth/drive'));
			$this->client->setAccessToken($params['token']);
			// if curl isn't available we're likely to run into
			// https://github.com/google/google-api-php-client/issues/59
			// - disable gzip to avoid it.
			if (!function_exists('curl_version') || !function_exists('curl_exec')) {
				$this->client->setClassConfig("Google_Http_Request", "disable_gzip", true);
			}
			// note: API connection is lazy
			$this->service = new \Google_Service_Drive($this->client);
			$token = json_decode($params['token'], true);
			$this->id = 'google::'.substr($params['client_id'], 0, 30).$token['created'];
		} else {
			throw new \Exception('Creating \OC\Files\Storage\Google storage failed');
		}
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Get the Google_Service_Drive_DriveFile object for the specified path.
	 * Returns false on failure.
	 * @param string $path
	 * @return \Google_Service_Drive_DriveFile|false
	 */
	private function getDriveFile($path) {
		// Remove leading and trailing slashes
		$path = trim($path, '/');
		if (isset($this->driveFiles[$path])) {
			return $this->driveFiles[$path];
		} else if ($path === '') {
			$root = $this->service->files->get('root');
			$this->driveFiles[$path] = $root;
			return $root;
		} else {
			// Google Drive SDK does not have methods for retrieving files by path
			// Instead we must find the id of the parent folder of the file
			$parentId = $this->getDriveFile('')->getId();
			$folderNames = explode('/', $path);
			$path = '';
			// Loop through each folder of this path to get to the file
			foreach ($folderNames as $name) {
				// Reconstruct path from beginning
				if ($path === '') {
					$path .= $name;
				} else {
					$path .= '/'.$name;
				}
				if (isset($this->driveFiles[$path])) {
					$parentId = $this->driveFiles[$path]->getId();
				} else {
					$q = "title='".$name."' and '".$parentId."' in parents and trashed = false";
					$result = $this->service->files->listFiles(array('q' => $q))->getItems();
					if (!empty($result)) {
						// Google Drive allows files with the same name, ownCloud doesn't
						if (count($result) > 1) {
							$this->onDuplicateFileDetected($path);
							return false;
						} else {
							$file = current($result);
							$this->driveFiles[$path] = $file;
							$parentId = $file->getId();
						}
					} else {
						// Google Docs have no extension in their title, so try without extension
						$pos = strrpos($path, '.');
						if ($pos !== false) {
							$pathWithoutExt = substr($path, 0, $pos);
							$file = $this->getDriveFile($pathWithoutExt);
							if ($file) {
								// Switch cached Google_Service_Drive_DriveFile to the correct index
								unset($this->driveFiles[$pathWithoutExt]);
								$this->driveFiles[$path] = $file;
								$parentId = $file->getId();
							} else {
								return false;
							}
						} else {
							return false;
						}
					}
				}
			}
			return $this->driveFiles[$path];
		}
	}

	/**
	 * Set the Google_Service_Drive_DriveFile object in the cache
	 * @param string $path
	 * @param Google_Service_Drive_DriveFile|false $file
	 */
	private function setDriveFile($path, $file) {
		$path = trim($path, '/');
		$this->driveFiles[$path] = $file;
		if ($file === false) {
			// Set all child paths as false
			$len = strlen($path);
			foreach ($this->driveFiles as $key => $file) {
				if (substr($key, 0, $len) === $path) {
					$this->driveFiles[$key] = false;
				}
			}
		}
	}

	/**
	 * Write a log message to inform about duplicate file names
	 * @param string $path
	 */
	private function onDuplicateFileDetected($path) {
		$about = $this->service->about->get();
		$user = $about->getName();
		\OCP\Util::writeLog('files_external',
			'Ignoring duplicate file name: '.$path.' on Google Drive for Google user: '.$user,
			\OCP\Util::INFO
		);
	}

	/**
	 * Generate file extension for a Google Doc, choosing Open Document formats for download
	 * @param string $mimetype
	 * @return string
	 */
	private function getGoogleDocExtension($mimetype) {
		if ($mimetype === self::DOCUMENT) {
			return 'odt';
		} else if ($mimetype === self::SPREADSHEET) {
			return 'ods';
		} else if ($mimetype === self::DRAWING) {
			return 'jpg';
		} else if ($mimetype === self::PRESENTATION) {
			// Download as .odp is not available
			return 'pdf';
		} else {
			return '';
		}
	}

	public function mkdir($path) {
		if (!$this->is_dir($path)) {
			$parentFolder = $this->getDriveFile(dirname($path));
			if ($parentFolder) {
				$folder = new \Google_Service_Drive_DriveFile();
				$folder->setTitle(basename($path));
				$folder->setMimeType(self::FOLDER);
				$parent = new \Google_Service_Drive_ParentReference();
				$parent->setId($parentFolder->getId());
				$folder->setParents(array($parent));
				$result = $this->service->files->insert($folder);
				if ($result) {
					$this->setDriveFile($path, $result);
				}
				return (bool)$result;
			}
		}
		return false;
	}

	public function rmdir($path) {
		if (!$this->isDeletable($path)) {
			return false;
		}
		if (trim($path, '/') === '') {
			$dir = $this->opendir($path);
			if(is_resource($dir)) {
				while (($file = readdir($dir)) !== false) {
					if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
						if (!$this->unlink($path.'/'.$file)) {
							return false;
						}
					}
				}
				closedir($dir);
			}
			$this->driveFiles = array();
			return true;
		} else {
			return $this->unlink($path);
		}
	}

	public function opendir($path) {
		// Remove leading and trailing slashes
		$path = trim($path, '/');
		$folder = $this->getDriveFile($path);
		if ($folder) {
			$files = array();
			$duplicates = array();
			$pageToken = true;
			while ($pageToken) {
				$params = array();
				if ($pageToken !== true) {
					$params['pageToken'] = $pageToken;
				}
				$params['q'] = "'".$folder->getId()."' in parents and trashed = false";
				$children = $this->service->files->listFiles($params);
				foreach ($children->getItems() as $child) {
					$name = $child->getTitle();
					// Check if this is a Google Doc i.e. no extension in name
					if ($child->getFileExtension() === ''
						&& $child->getMimeType() !== self::FOLDER
					) {
						$name .= '.'.$this->getGoogleDocExtension($child->getMimeType());
					}
					if ($path === '') {
						$filepath = $name;
					} else {
						$filepath = $path.'/'.$name;
					}
					// Google Drive allows files with the same name, ownCloud doesn't
					// Prevent opendir() from returning any duplicate files
					$key = array_search($name, $files);
					if ($key !== false || isset($duplicates[$filepath])) {
						if (!isset($duplicates[$filepath])) {
							$duplicates[$filepath] = true;
							$this->setDriveFile($filepath, false);
							unset($files[$key]);
							$this->onDuplicateFileDetected($filepath);
						}
					} else {
						// Cache the Google_Service_Drive_DriveFile for future use
						$this->setDriveFile($filepath, $child);
						$files[] = $name;
					}
				}
				$pageToken = $children->getNextPageToken();
			}
			\OC\Files\Stream\Dir::register('google'.$path, $files);
			return opendir('fakedir://google'.$path);
		} else {
			return false;
		}
	}

	public function stat($path) {
		$file = $this->getDriveFile($path);
		if ($file) {
			$stat = array();
			if ($this->filetype($path) === 'dir') {
				$stat['size'] = 0;
			} else {
				// Check if this is a Google Doc
				if ($this->getMimeType($path) !== $file->getMimeType()) {
					// Return unknown file size
					$stat['size'] = \OCP\Files\FileInfo::SPACE_UNKNOWN;
				} else {
					$stat['size'] = $file->getFileSize();
				}
			}
			$stat['atime'] = strtotime($file->getLastViewedByMeDate());
			$stat['mtime'] = strtotime($file->getModifiedDate());
			$stat['ctime'] = strtotime($file->getCreatedDate());
			return $stat;
		} else {
			return false;
		}
	}

	public function filetype($path) {
		if ($path === '') {
			return 'dir';
		} else {
			$file = $this->getDriveFile($path);
			if ($file) {
				if ($file->getMimeType() === self::FOLDER) {
					return 'dir';
				} else {
					return 'file';
				}
			} else {
				return false;
			}
		}
	}

	public function isUpdatable($path) {
		$file = $this->getDriveFile($path);
		if ($file) {
			return $file->getEditable();
		} else {
			return false;
		}
	}

	public function file_exists($path) {
		return (bool)$this->getDriveFile($path);
	}

	public function unlink($path) {
		$file = $this->getDriveFile($path);
		if ($file) {
			$result = $this->service->files->trash($file->getId());
			if ($result) {
				$this->setDriveFile($path, false);
			}
			return (bool)$result;
		} else {
			return false;
		}
	}

	public function rename($path1, $path2) {
		$file = $this->getDriveFile($path1);
		if ($file) {
			if (dirname($path1) === dirname($path2)) {
				$file->setTitle(basename(($path2)));
			} else {
				// Change file parent
				$parentFolder2 = $this->getDriveFile(dirname($path2));
				if ($parentFolder2) {
					$parent = new \Google_Service_Drive_ParentReference();
					$parent->setId($parentFolder2->getId());
					$file->setParents(array($parent));
				} else {
					return false;
				}
			}
			// We need to get the object for the existing file with the same
			// name (if there is one) before we do the patch. If oldfile
			// exists and is a directory we have to delete it before we
			// do the rename too.
			$oldfile = $this->getDriveFile($path2);
			if ($oldfile && $this->is_dir($path2)) {
				$this->rmdir($path2);
				$oldfile = false;
			}
			$result = $this->service->files->patch($file->getId(), $file);
			if ($result) {
				$this->setDriveFile($path1, false);
				$this->setDriveFile($path2, $result);
				if ($oldfile) {
					$this->service->files->delete($oldfile->getId());
				}
			}
			return (bool)$result;
		} else {
			return false;
		}
	}

	public function fopen($path, $mode) {
		$pos = strrpos($path, '.');
		if ($pos !== false) {
			$ext = substr($path, $pos);
		} else {
			$ext = '';
		}
		switch ($mode) {
			case 'r':
			case 'rb':
				$file = $this->getDriveFile($path);
				if ($file) {
					$exportLinks = $file->getExportLinks();
					$mimetype = $this->getMimeType($path);
					$downloadUrl = null;
					if ($exportLinks && isset($exportLinks[$mimetype])) {
						$downloadUrl = $exportLinks[$mimetype];
					} else {
						$downloadUrl = $file->getDownloadUrl();
					}
					if (isset($downloadUrl)) {
						$request = new \Google_Http_Request($downloadUrl, 'GET', null, null);
						$httpRequest = $this->client->getAuth()->authenticatedRequest($request);
						if ($httpRequest->getResponseHttpCode() == 200) {
							$tmpFile = \OC_Helper::tmpFile($ext);
							$data = $httpRequest->getResponseBody();
							file_put_contents($tmpFile, $data);
							return fopen($tmpFile, $mode);
						}
					}
				}
				return false;
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
				$tmpFile = \OC_Helper::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'rb');
					file_put_contents($tmpFile, $source);
				}
				self::$tempFiles[$tmpFile] = $path;
				return fopen('close://'.$tmpFile, $mode);
		}
	}

	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
			$path = self::$tempFiles[$tmpFile];
			$parentFolder = $this->getDriveFile(dirname($path));
			if ($parentFolder) {
				// TODO Research resumable upload
				$mimetype = \OC_Helper::getMimeType($tmpFile);
				$data = file_get_contents($tmpFile);
				$params = array(
					'data' => $data,
					'mimeType' => $mimetype,
					'uploadType' => 'media'
				);
				$result = false;
				if ($this->file_exists($path)) {
					$file = $this->getDriveFile($path);
					$result = $this->service->files->update($file->getId(), $file, $params);
				} else {
					$file = new \Google_Service_Drive_DriveFile();
					$file->setTitle(basename($path));
					$file->setMimeType($mimetype);
					$parent = new \Google_Service_Drive_ParentReference();
					$parent->setId($parentFolder->getId());
					$file->setParents(array($parent));
					$result = $this->service->files->insert($file, $params);
				}
				if ($result) {
					$this->setDriveFile($path, $result);
				}
			}
			unlink($tmpFile);
		}
	}

	public function getMimeType($path) {
		$file = $this->getDriveFile($path);
		if ($file) {
			$mimetype = $file->getMimeType();
			// Convert Google Doc mimetypes, choosing Open Document formats for download
			if ($mimetype === self::FOLDER) {
				return 'httpd/unix-directory';
			} else if ($mimetype === self::DOCUMENT) {
				return 'application/vnd.oasis.opendocument.text';
			} else if ($mimetype === self::SPREADSHEET) {
				return 'application/x-vnd.oasis.opendocument.spreadsheet';
			} else if ($mimetype === self::DRAWING) {
				return 'image/jpeg';
			} else if ($mimetype === self::PRESENTATION) {
				// Download as .odp is not available
				return 'application/pdf';
			} else {
				return $mimetype;
			}
		} else {
			return false;
		}
	}

	public function free_space($path) {
		$about = $this->service->about->get();
		return $about->getQuotaBytesTotal() - $about->getQuotaBytesUsed();
	}

	public function touch($path, $mtime = null) {
		$file = $this->getDriveFile($path);
		$result = false;
		if ($file) {
			if (isset($mtime)) {
				// This is just RFC3339, but frustratingly, GDrive's API *requires*
				// the fractions portion be present, while no handy PHP constant
				// for RFC3339 or ISO8601 includes it. So we do it ourselves.
				$file->setModifiedDate(date('Y-m-d\TH:i:s.uP', $mtime));
				$result = $this->service->files->patch($file->getId(), $file, array(
					'setModifiedDate' => true,
				));
			} else {
				$result = $this->service->files->touch($file->getId());
			}
		} else {
			$parentFolder = $this->getDriveFile(dirname($path));
			if ($parentFolder) {
				$file = new \Google_Service_Drive_DriveFile();
				$file->setTitle(basename($path));
				$parent = new \Google_Service_Drive_ParentReference();
				$parent->setId($parentFolder->getId());
				$file->setParents(array($parent));
				$result = $this->service->files->insert($file);
			}
		}
		if ($result) {
			$this->setDriveFile($path, $result);
		}
		return (bool)$result;
	}

	public function test() {
		if ($this->free_space('')) {
			return true;
		}
		return false;
	}

	public function hasUpdated($path, $time) {
		$appConfig = \OC::$server->getAppConfig();
		if ($this->is_file($path)) {
			return parent::hasUpdated($path, $time);
		} else {
			// Google Drive doesn't change modified times of folders when files inside are updated
			// Instead we use the Changes API to see if folders have been updated, and it's a pain
			$folder = $this->getDriveFile($path);
			if ($folder) {
				$result = false;
				$folderId = $folder->getId();
				$startChangeId = $appConfig->getValue('files_external', $this->getId().'cId');
				$params = array(
					'includeDeleted' => true,
					'includeSubscribed' => true,
				);
				if (isset($startChangeId)) {
					$startChangeId = (int)$startChangeId;
					$largestChangeId = $startChangeId;
					$params['startChangeId'] = $startChangeId + 1;
				} else {
					$largestChangeId = 0;
				}
				$pageToken = true;
				while ($pageToken) {
					if ($pageToken !== true) {
						$params['pageToken'] = $pageToken;
					}
					$changes = $this->service->changes->listChanges($params);
					if ($largestChangeId === 0 || $largestChangeId === $startChangeId) {
						$largestChangeId = $changes->getLargestChangeId();
					}
					if (isset($startChangeId)) {
						// Check if a file in this folder has been updated
						// There is no way to filter by folder at the API level...
						foreach ($changes->getItems() as $change) {
							$file = $change->getFile();
							if ($file) {
								foreach ($file->getParents() as $parent) {
									if ($parent->getId() === $folderId) {
										$result = true;
									// Check if there are changes in different folders
									} else if ($change->getId() <= $largestChangeId) {
										// Decrement id so this change is fetched when called again
										$largestChangeId = $change->getId();
										$largestChangeId--;
									}
								}
							}
						}
						$pageToken = $changes->getNextPageToken();
					} else {
						// Assuming the initial scan just occurred and changes are negligible
						break;
					}
				}
				$appConfig->setValue('files_external', $this->getId().'cId', $largestChangeId);
				return $result;
			}
		}
		return false;
	}

	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		if (function_exists('curl_init')) {
			return true;
		} else {
			return array('curl');
		}
	}

}
