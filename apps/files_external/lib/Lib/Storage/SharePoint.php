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
use OCP\Files\FileInfo;
use Office365\PHP\Client\Runtime\Auth\AuthenticationContext;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\SPList;

class SharePoint extends Common {
	const SP_PROPERTY_SIZE = 'Length';
	const SP_PROPERTY_MTIME = 'TimeLastModified';

	/** @var  string */
	protected $server;

	/** @var  string */
	protected $documentLibrary;

	/** @var  SPList */
	protected $documentLibraryItem;

	/** @var  string */
	protected $authUser;

	/** @var  string */
	protected $authPwd;

	/** @var  AuthenticationContext */
	protected $authContext;

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
		$this->ensureConnection();
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
		$serverUrl = $this->formatPath($path);
		$file = $this->fetchFileOrFolder($serverUrl, [self::SP_PROPERTY_SIZE, self::SP_PROPERTY_MTIME]);
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

		// If we do not get a size or mtime from SP, we treat it as an error
		// thus returning false, according to PHP documentation on stat()
		return false;
	}

	/**
	 * @param string $path
	 * @param array $properties
	 * @return File|Folder
	 * @throws \Exception
	 */
	private function fetchFileOrFolder($path, array $properties = null) {
		# room for optimization: if "." present in path, try file first,
		# otherwise folder

		# Attempt 1: fetch a file
		try {
			$file = $this->context->getWeb()->getFileByServerRelativeUrl($path);
			$this->loadAndExecute($file, $properties);
			return $file;
		} catch (\Exception $e) {
			if(preg_match('/^The file \/.* does not exist\.$/', $e->getMessage()) !== 1) {
				# Unexpected Exception, pass it on
				throw $e;
			}
		}

		# Attempt 2: fetch a folder
		try {
			$this->createClientContext();	// otherwise the old query will be repeated
			return $this->fetchFolder($path, $properties);
		} catch (\Exception $e) {
			if($e->getMessage() !== 'Unknown Error') { // yes, SP returns this
				throw $e;
			}
		}

		# Nothing succeeded, quit with not found
		throw new NotFoundException('File or Folder not found');
	}

	private function fetchFolder($relativeServerPath, array $properties = null) {
		$folder = $this->context->getWeb()->getFolderByServerRelativeUrl($relativeServerPath);
		$this->loadAndExecute($folder, $properties);
		return $folder;
	}

	private function loadAndExecute(ClientObject $object, array $properties = null) {
		$this->context->load($object, $properties);
		$this->context->executeQuery();
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
			$object = $this->fetchFileOrFolder($serverUrl, []);
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
			// exisiting files are checked) and measurements.
			$this->fetchFileOrFolder($serverUrl);
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
				$fsObject = $this->fetchFileOrFolder($path, $asFile);
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
	 * @return SPList
	 * @throws NotFoundException
	 */
	private function getDocumentLibrary() {
		if(!is_null($this->documentLibraryItem)) {
			return $this->documentLibraryItem;
		}

		$lists = $this->context->getWeb()->getLists()->filter('Title eq \'' . $this->documentLibrary . '\'')->top(1);
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
		$this->authContext = $this->contextsFactory->getAuthContext($this->authUser, $this->authPwd);
		$this->authContext->AuthType = CURLAUTH_NTLM;		# Basic auth does not work somehow…
		$this->createClientContext();
		# Auth is not triggered yet. This will happen when something is requested from Sharepoint (on demand), e.g.:
	}

	/**
	 * (re)creates the sharepoint client context
	 */
	private function createClientContext() {
		$this->context = null;
		$this->context = $this->contextsFactory->getClientContext($this->server, $this->authContext);
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
