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

require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_DriveService.php';

class Google extends \OC\Files\Storage\Common {

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
			$client = new \Google_Client();
			$client->setClientId($params['client_id']);
			$client->setClientSecret($params['client_secret']);
			$client->setScopes(array('https://www.googleapis.com/auth/drive'));
			$client->setUseObjects(true);
			$client->setAccessToken($params['token']);
			$this->service = new \Google_DriveService($client);
			$this->root = isset($params['root']) ? $params['root'] : '';
			$token = json_decode($params['token'], true);
			$this->id = 'google::'.$params['client_id'].$token['created'];
		} else {
			throw new \Exception('Creating \OC\Files\Storage\Google storage failed');
		}
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Get the Google_DriveFile object for the specified path
	 * @param string $path
	 * @return Google_DriveFile
	 */
	private function getDriveFile($path) {
		// Remove leading and trailing slashes
		$path = trim($this->root.$path, '/');
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
					$q = "title='".$name."' and '".$parentId."' in parents";
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
								// Switch cached Google_DriveFile to the correct index
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
	 * Write a log message to inform about duplicate file names
	 * @param string $path
	 */
	private function onDuplicateFileDetected($path) {
		$about = $this->service->about->get();
		$user = $about->getName();
		\OCP\Util::writeLog('files_external',
			'Ignoring duplicate file name: '.$path.' on Google Drive for Google user: '.$user,
			\OCP\Util::INFO);
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
		$parentFolder = $this->getDriveFile(dirname($path));
		if ($parentFolder) {
			$folder = new \Google_DriveFile();
			$folder->setTitle(basename($path));
			$folder->setMimeType(self::FOLDER);
			$parent = new \Google_ParentReference();
			$parent->setId($parentFolder->getId());
			$folder->setParents(array($parent));
			return (bool)$this->service->files->insert($folder);
		} else {
			return false;
		}
	}

	public function rmdir($path) {
		return $this->unlink($path);
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
				$params['q'] = "'".$folder->getId()."' in parents";
				$children = $this->service->files->listFiles($params);
				foreach ($children->getItems() as $child) {
					$name = $child->getTitle();
					// Check if this is a Google Doc i.e. no extension in name
					if ($child->getFileExtension() == ''
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
					if (isset($this->driveFiles[$filepath]) && !isset($duplicates[$filepath])) {
						// Save this key to unset later in case there are more than 2 duplicates
						$duplicates[$filepath] = $name;
					} else {
						// Cache the Google_DriveFile for future use
						$this->driveFiles[$filepath] = $child;
						$files[] = $name;
					}
				}
				$pageToken = $children->getNextPageToken();
			}
			// Remove all duplicate files
			foreach ($duplicates as $filepath => $name) {
				unset($this->driveFiles[$filepath]);
				$key = array_search($name, $files);
				unset($files[$key]);
				$this->onDuplicateFileDetected($filepath);
			}
			// Reindex $files array if duplicates were removed
			// This is necessary for \OC\Files\Stream\Dir
			if (!empty($duplicates)) {
				$files = array_values($files);
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
				$stat['size'] = $file->getFileSize();
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

	public function isReadable($path) {
		return true;
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
			return (bool)$this->service->files->trash($file->getId());
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
					$parent = new \Google_ParentReference();
					$parent->setId($parentFolder2->getId());
					$file->setParents(array($parent));
				}
			}
			return (bool)$this->service->files->patch($file->getId(), $file);
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
						$request = new \Google_HttpRequest($downloadUrl, 'GET', null, null);
						$httpRequest = \Google_Client::$io->authenticatedRequest($request);
						if ($httpRequest->getResponseHttpCode() == 200) {
							$tmpFile = \OC_Helper::tmpFile($ext);
							$data = $httpRequest->getResponseBody();
							file_put_contents($tmpFile, $data);
							return fopen($tmpFile, $mode);
						}
					}
				}
				return null;
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
				$file = new \Google_DriveFile();
				$file->setTitle(basename($path));
				$mimetype = \OC_Helper::getMimeType($tmpFile);
				$file->setMimeType($mimetype);
				$parent = new \Google_ParentReference();
				$parent->setId($parentFolder->getId());
				$file->setParents(array($parent));
				// TODO Research resumable upload
				$data = file_get_contents($tmpFile);
				$params = array(
					'data' => $data,
					'mimeType' => $mimetype,
				);
				if ($this->file_exists($path)) {
					$this->service->files->update($file->getId(), $file, $params);
				} else {
					$this->service->files->insert($file, $params);
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
		if ($file) {
			if (isset($mtime)) {
				$file->setModifiedDate($mtime);
				$this->service->files->patch($file->getId(), $file, array(
					'setModifiedDate' => true,
				));
			} else {
				return (bool)$this->service->files->touch($file->getId());
			}
		} else {
			return false;
		}
	}

	public function test() {
		if ($this->free_space('')) {
			return true;
		}
		return false;
	}

}