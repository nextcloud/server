<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\Files_External\Lib\Storage;

use Icewind\Streams\IteratorDirectory;
use OC\Files\Storage\Common;
use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use OCA\Files_External\Lib\SharePoint\NotFoundException;
use OCA\Files_External\Lib\SharePoint\SharePointClient;
use OCA\Files_External\Lib\SharePoint\SharePointClientFactory;
use OCP\Files\FileInfo;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\ListItem;

class SharePoint extends Common {
	const SP_PROPERTY_SIZE = 'Length';
	const SP_PROPERTY_MTIME = 'TimeLastModified';

	/** @var  string */
	protected $server;

	/** @var  string */
	protected $documentLibrary;

	/** @var  string */
	protected $authUser;

	/** @var  string */
	protected $authPwd;

	/** @var  SharePointClient */
	protected $spClient;

	/** @var ContextsFactory */
	private $contextsFactory;

	public function __construct($parameters) {
		$this->server = $parameters['host'];
		$this->documentLibrary = $parameters['documentLibrary'];

		if(strpos($this->documentLibrary, '"') !== false) {
			// they are, amongst others, not allowed and we use it in the filter
			// cf. https://support.microsoft.com/en-us/kb/2933738
			// TODO: verify, it talks about files and folders mostly
			throw new \InvalidArgumentException('Illegal character in Document Library Name');
		}

		if(!isset($parameters['user']) || !isset($parameters['password'])) {
			throw new \UnexpectedValueException('No user or password given');
		}
		$this->authUser = $parameters['user'];
		$this->authPwd  = $parameters['password'];

		$this->fixDI($parameters);
	}

	/**
	 * Get the identifier for the storage,
	 * the returned id should be the same for every storage object that is created with the same parameters
	 * and two storage objects with the same id should refer to two storages that display the same files.
	 *
	 * @return string
	 * @since 6.0.0
	 */
	public function getId() {
		return 'SharePoint::' . $this->server . '::' . $this->documentLibrary . '::' . $this->authUser;
	}

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 * implementations need to implement a recursive mkdir
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function mkdir($path) {
		// TODO: Implement mkdir() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function rmdir($path) {
		// TODO: Implement rmdir() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.opendir.php
	 *
	 * @param string $path
	 * @return resource|false
	 * @since 6.0.0
	 */
	public function opendir($path) {
		try {
			$serverUrl = $this->formatPath($path);
			//$collections = $this->spClient->fetchFolderContents($serverUrl, ['Name', 'ListItemAllFields']);	// does not work for some reason :(
			$collections = $this->spClient->fetchFolderContents($serverUrl);
			$files = [];

			foreach ($collections as $collection) {
				/** @var File[]|Folder[] $items */
				$items = $collection->getData();
				foreach ($items as $item) {
					/** @var ListItem $fields */
					$id = $item->getListItemAllFields()->getProperty('Id');
					$hidden = $item->getListItemAllFields()->getProperty('Hidden'); // TODO: get someone to test this in SP 2013
					if($hidden === false || $id !== null) {
						// avoids listing hidden "Forms" folder (and its contents).
						// Have not found a different mechanism to detect whether
						// a file or folder is hidden. There used to be a Hidden
						// field, but seems to have gone (since SP 2016?).
						$files[] = $item->getProperty('Name');
					}
				}
			}

			return IteratorDirectory::wrap($files);
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.stat.php
	 * only the following keys are required in the result: size and mtime
	 *
	 * @param string $path
	 * @return array|false
	 * @since 6.0.0
	 */
	public function stat($path) {
		$serverUrl = $this->formatPath($path);
		try {
			$file = $this->spClient->fetchFileOrFolder($serverUrl, [self::SP_PROPERTY_SIZE, self::SP_PROPERTY_MTIME]);
		} catch (\Exception $e) {
			return false;
		}
		$stat = [
			// int64, size in bytes, excluding the size of any Web Parts that are used in the file.
			'size'  => $file->getProperty(self::SP_PROPERTY_SIZE) ?: FileInfo::SPACE_UNKNOWN,
			'mtime' => $file->getProperty(self::SP_PROPERTY_MTIME),
			// no property in SP 2013 & 2016, other storages do the same  :speak_no_evil:
			'atime' => time(),
		];

		if(!is_null($stat['mtime'])) {
			return $stat;
		}

		// If we do not get a mtime from SP, we treat it as an error
		// thus returning false, according to PHP documentation on stat()
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.filetype.php
	 *
	 * @param string $path
	 * @return false|string
	 * @throws \Exception
	 * @since 6.0.0
	 */
	public function filetype($path) {
		try {
			$serverUrl = $this->formatPath($path);
			$object = $this->spClient->fetchFileOrFolder($serverUrl, []);
		} catch (NotFoundException $e) {
			return false;
		}
		if($object instanceof File) {
			return 'file';
		} else if($object instanceof Folder) {
			return 'dir';
		} else {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.file_exists.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function file_exists($path) {
		try {
			$serverUrl = $this->formatPath($path);
			// alternative approach is to use a CAML query instead of querying
			// for file and folder. It is not necessarily faster, though.
			// Would need evaluation of typical use cases (I assume most often
			// existing files are checked) and measurements.
			$this->spClient->fetchFileOrFolder($serverUrl, []);
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function unlink($path) {
		$path = trim($path);
		if($path === '/' || $path === '') {
			return false;
		}
		foreach([true, false] as $asFile) {
			try {
				$fsObject = $this->spClient->fetchFileOrFolder($path, $asFile);
				$fsObject->deleteObject();
				return true;
			} catch(\Exception $e) {
				// NOOP
			}
		}
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource|false
	 * @since 6.0.0
	 */
	public function fopen($path, $mode) {
		// TODO: Implement fopen() method.
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool
	 * @since 6.0.0
	 */
	public function touch($path, $mtime = null) {
		// TODO: Implement touch() method.
		return true;
	}


	/**
	 * work around dependency injection issues so we can test this class properly
	 *
	 * @param array $parameters
	 */
	private function fixDI(array $parameters) {
		if(isset($parameters['contextFactory'])
			&& $parameters['contextFactory'] instanceof ContextsFactory)
		{
			$this->contextsFactory = $parameters['contextFactory'];
		} else {
			$this->contextsFactory = new ContextsFactory();
		}

		if(isset($parameters['sharePointClientFactory'])
			&& $parameters['sharePointClientFactory'] instanceof SharePointClientFactory)
		{
			$spcFactory = $parameters['sharePointClientFactory'];
		} else {
			$spcFactory = new SharePointClientFactory();
		}
		$this->spClient = $spcFactory->getClient(
			$this->contextsFactory,
			$this->server,
			[ 'user' => $this->authUser, 'password' => $this->authPwd],
			$this->documentLibrary
		);
	}

	/**
	 * creates the relative server "url" out of the provided path
	 *
	 * @param $path
	 * @return string
	 */
	private function formatPath($path) {
		$path = trim($path, '/');
		$serverUrl = '/' . $this->documentLibrary;
		if($path !== '') {
			$serverUrl .= '/' . $path;
		}
		return $serverUrl;
	}

}
