<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Francesco Rovelli <francesco.rovelli@gmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_External\Lib\Storage;

use GuzzleHttp\Exception\RequestException;
use Icewind\Streams\IteratorDirectory;
use Icewind\Streams\RetryWrapper;

set_include_path(get_include_path().PATH_SEPARATOR.
	\OC_App::getAppPath('files_external').'/3rdparty/google-api-php-client/src');
require_once 'Google/autoload.php';

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
	const MAP = 'application/vnd.google-apps.map';

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
			throw new \Exception('Creating Google storage failed');
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
		if ($path === '.') {
			$path = '';
		}
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
					$q = "title='" . str_replace("'","\\'", $name) . "' and '" . str_replace("'","\\'", $parentId) . "' in parents and trashed = false";
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
							if ($file && $this->isGoogleDocFile($file)) {
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
	 * @param \Google_Service_Drive_DriveFile|false $file
	 */
	private function setDriveFile($path, $file) {
		$path = trim($path, '/');
		$this->driveFiles[$path] = $file;
		if ($file === false) {
			// Remove all children
			$len = strlen($path);
			foreach ($this->driveFiles as $key => $file) {
				if (substr($key, 0, $len) === $path) {
					unset($this->driveFiles[$key]);
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

	/**
	 * Returns whether the given drive file is a Google Doc file
	 *
	 * @param \Google_Service_Drive_DriveFile
	 *
	 * @return true if the file is a Google Doc file, false otherwise
	 */
	private function isGoogleDocFile($file) {
		return $this->getGoogleDocExtension($file->getMimeType()) !== '';
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
				$params['q'] = "'" . str_replace("'","\\'", $folder->getId()) . "' in parents and trashed = false";
				$children = $this->service->files->listFiles($params);
				foreach ($children->getItems() as $child) {
					$name = $child->getTitle();
					// Check if this is a Google Doc i.e. no extension in name
					$extension = $child->getFileExtension();
					if (empty($extension)) {
						if ($child->getMimeType() === self::MAP) {
							continue; // No method known to transfer map files, ignore it
						} else if ($child->getMimeType() !== self::FOLDER) {
							$name .= '.'.$this->getGoogleDocExtension($child->getMimeType());
						}
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
			return IteratorDirectory::wrap($files);
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
				if ($this->isGoogleDocFile($file)) {
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
			$newFile = $this->getDriveFile($path2);
			if (dirname($path1) === dirname($path2)) {
				if ($newFile) {
					// rename to the name of the target file, could be an office file without extension
					$file->setTitle($newFile->getTitle());
				} else {
					$file->setTitle(basename(($path2)));
				}
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
				if ($oldfile && $newFile) {
					// only delete if they have a different id (same id can happen for part files)
					if ($newFile->getId() !== $oldfile->getId()) {
						$this->service->files->delete($oldfile->getId());
					}
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
						$httpRequest = $this->client->getAuth()->sign($request);
						// the library's service doesn't support streaming, so we use Guzzle instead
						$client = \OC::$server->getHTTPClientService()->newClient();
						try {
							$response = $client->get($downloadUrl, [
								'headers' => $httpRequest->getRequestHeaders(),
								'stream' => true,
								'verify' => realpath(__DIR__ . '/../../../3rdparty/google-api-php-client/src/Google/IO/cacerts.pem'),
							]);
						} catch (RequestException $e) {
							if(!is_null($e->getResponse())) {
								if ($e->getResponse()->getStatusCode() === 404) {
									return false;
								} else {
									throw $e;
								}
							} else {
								throw $e;
							}
						}

						$handle = $response->getBody();
						return RetryWrapper::wrap($handle);
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
				$tmpFile = \OCP\Files::tmpFile($ext);
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
				$mimetype = \OC::$server->getMimeTypeDetector()->detect($tmpFile);
				$params = array(
					'mimeType' => $mimetype,
					'uploadType' => 'media'
				);
				$result = false;

				$chunkSizeBytes = 10 * 1024 * 1024;

				$useChunking = false;
				$size = filesize($tmpFile);
				if ($size > $chunkSizeBytes) {
					$useChunking = true;
				} else {
					$params['data'] = file_get_contents($tmpFile);
				}

				if ($this->file_exists($path)) {
					$file = $this->getDriveFile($path);
					$this->client->setDefer($useChunking);
					$request = $this->service->files->update($file->getId(), $file, $params);
				} else {
					$file = new \Google_Service_Drive_DriveFile();
					$file->setTitle(basename($path));
					$file->setMimeType($mimetype);
					$parent = new \Google_Service_Drive_ParentReference();
					$parent->setId($parentFolder->getId());
					$file->setParents(array($parent));
					$this->client->setDefer($useChunking);
					$request = $this->service->files->insert($file, $params);
				}

				if ($useChunking) {
					// Create a media file upload to represent our upload process.
					$media = new \Google_Http_MediaFileUpload(
						$this->client,
						$request,
						'text/plain',
						null,
						true,
						$chunkSizeBytes
					);
					$media->setFileSize($size);

					// Upload the various chunks. $status will be false until the process is
					// complete.
					$status = false;
					$handle = fopen($tmpFile, 'rb');
					while (!$status && !feof($handle)) {
						$chunk = fread($handle, $chunkSizeBytes);
						$status = $media->nextChunk($chunk);
					}

					// The final value of $status will be the data from the API for the object
					// that has been uploaded.
					$result = false;
					if ($status !== false) {
						$result = $status;
					}

					fclose($handle);
				} else {
					$result = $request;
				}

				// Reset to the client to execute requests immediately in the future.
				$this->client->setDefer(false);

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
				// use extension-based detection, could be an encrypted file
				return parent::getMimeType($path);
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
		return true;
	}

}
