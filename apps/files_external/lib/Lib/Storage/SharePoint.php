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

use OC\Files\Storage\Common;
use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use OCA\Files_External\Lib\SharePoint\NotFoundException;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\SPList;

class SharePoint extends Common {

	protected $server;

	protected $documentLibrary;

	/** @var  SPList */
	protected $documentLibraryItem;

	protected $authUser;

	protected $authPwd;

	/** @var  ClientContext */
	protected $context;

	/** @var  ContextsFactory */
	protected $contextsFactory;

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
		// TODO: Implement opendir() method.
		return false;
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
		$this->ensureConnection();

		$path = trim($path, '/');

		if($path === '/' || $path === '') {
			$fsObject = $this->getDocumentLibrary()->getRootFolder();
			$properties = $fsObject->getProperties(); // TODO: see what we retrieve here
		} else {
			// TODO: verify that try-catch approach works
			try {
				$fsObject = $this->fetchFileOrFolder($path, true);	// likely we need to modify path since we are not operating on the document library
			} catch (\Exception $e) {
				// it can be a folder, too
				$fsObject = $this->fetchFileOrFolder($path, false);
			}
		}
		// FIXME: getProperty may return null
		// FIXME: Folder does not have such properties, according to doc – traversing through all files needed
		$stat = [
			// int64, size in bytes, excluding the size of any Web Parts that are used in the file.
			'size'  => $fsObject->getProperty('Length'),
			'mtime' => $fsObject->getProperty('TimeLastModified'),
			// no property in SP 2013, other storages do the same  :speak_no_evil:
			'atime' => time(),
		];

		if(isset($stat) && !is_null($stat['size']) && !is_null($stat['mtime'])) {
			return $stat;
		}

		// If we do not get a size or mtime from SP, we treat it as an error
		// thus returning false, according to PHP documentation on stat()
		return false;
	}

	/**
	 * @param string $path
	 * @param bool $tryFile
	 * @return File|Folder
	 */
	private function fetchFileOrFolder($path, $tryFile) {
		if($tryFile) {
			return $this->context->getWeb()->getFileByServerRelativeUrl($path);
		} else {
			return $this->context->getWeb()->getFolderByServerRelativeUrl($path);
		}
	}

	/**
	 * see http://php.net/manual/en/function.filetype.php
	 *
	 * @param string $path
	 * @return string|false
	 * @since 6.0.0
	 */
	public function filetype($path) {
		$path = trim($path);
		if($path === '/' || $path === '') {
			return 'dir';
		}
		try {
			$this->fetchFileOrFolder($path, true);
			return 'file';
		} catch(\Exception $e) {
			try {
				$this->fetchFileOrFolder($path, false);
				return 'dir';
			} catch (\Exception $e) {
				// NOOP
			}
		}
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.file_exists.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function file_exists($path) {
		// TODO: Implement file_exists() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function unlink($path) {
		// TODO: Implement unlink() method.
		return true;
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
	 * @return SPList
	 * @throws NotFoundException
	 */
	private function getDocumentLibrary() {
		if(!is_null($this->documentLibraryItem)) {
			return $this->documentLibraryItem;
		}

		$lists = $this->context->getWeb()->getLists()->filter('Title eq "' . $this->documentLibrary . '"')->top(1);
		$this->context->load($lists)->executeQuery();
		if ($lists->getCount() == 1) {
			$this->documentLibraryItem = $lists->getData()[0];
			return $this->documentLibraryItem;
		}

		throw new NotFoundException('List not found');
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
	}

	/**
	 * Set up necessary contexts for authentication and access to SharePoint
	 *
	 * @throws \InvalidArgumentException
	 */
	private function ensureConnection() {
		if($this->context instanceof ClientContext) {
			return;
		}

		if(!is_string($this->authUser) || empty($this->authUser)) {
			throw new \InvalidArgumentException('No user given');
		}
		if(!is_string($this->authPwd) || empty($this->authPwd)) {
			throw new \InvalidArgumentException('No password given');
		}
		$authContext   = $this->contextsFactory->getAuthContext($this->authUser, $this->authPwd);
		$this->context = $this->contextsFactory->getClientContext($this->server, $authContext);
	}
}
