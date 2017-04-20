<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\Files_External\Lib\SharePoint;

use Office365\PHP\Client\Runtime\Auth\AuthenticationContext;
use Office365\PHP\Client\Runtime\ClientObject;
use Office365\PHP\Client\Runtime\ClientObjectCollection;
use Office365\PHP\Client\Runtime\Utilities\RequestOptions;
use Office365\PHP\Client\Runtime\Utilities\Requests;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;
use Office365\PHP\Client\SharePoint\FileCreationInformation;
use Office365\PHP\Client\SharePoint\Folder;
use Office365\PHP\Client\SharePoint\SPList;

class SharePointClient {
	/** @var  ClientContext */
	protected $context;

	/** @var  AuthenticationContext */
	protected $authContext;

	/** @var  SPList */
	protected $documentLibrary;

	/** @var ContextsFactory */
	private $contextsFactory;

	/** @var  string */
	private $sharePointUrl;

	/** @var string[] */
	private $credentials;

	/** @var  string */
	private $documentLibraryTitle;

	public function __construct(ContextsFactory $contextsFactory, $sharePointUrl, array $credentials, $documentLibraryTitle) {
		$this->contextsFactory = $contextsFactory;
		$this->sharePointUrl = $sharePointUrl;
		$this->credentials = $credentials;
		$this->documentLibraryTitle = $documentLibraryTitle;
	}

	/**
	 * @param string $path
	 * @param array $properties
	 * @return File|Folder
	 * @throws \Exception
	 */
	public function fetchFileOrFolder($path, array $properties = null) {
		$fetchFileFunc = function ($path, $props) { return $this->fetchFile($path, $props);};
		$fetchFolderFunc = function ($path, $props) { return $this->fetchFolder($path, $props);};
		$fetchers = [ $fetchFileFunc, $fetchFolderFunc ];
		if(strpos($path, '.') === false) {
			$fetchers = array_reverse($fetchers);
		}

		foreach ($fetchers as $fetchFunction) {
			try {
				$instance = call_user_func_array($fetchFunction, [$path, $properties]);
				return $instance;
			} catch (\Exception $e) {
				if(preg_match('/^The file \/.* does not exist\.$/', $e->getMessage()) !== 1
					&& $e->getMessage() !== 'Unknown Error'
					&& $e->getMessage() !== 'File Not Found.'
				) {
					# Unexpected Exception, pass it on
					throw $e;
				}
			}
		}

		# Nothing succeeded, quit with not found
		throw new NotFoundException('File or Folder not found');
	}

	public function fetchFile($relativeServerPath, array $properties = null) {
		$this->ensureConnection();
		$file = $this->context->getWeb()->getFileByServerRelativeUrl($relativeServerPath);
		$this->loadAndExecute($file, $properties);
		return $file;
	}

	public function fetchFolder($relativeServerPath, array $properties = null) {
		$this->ensureConnection();
		$folder = $this->context->getWeb()->getFolderByServerRelativeUrl($relativeServerPath);
		$this->loadAndExecute($folder, $properties);
		return $folder;
	}

	/**
	 * adds a folder on the given server path
	 *
	 * @param string $relativeServerPath
	 * @return Folder
	 * @throws \Exception
	 */
	public function createFolder($relativeServerPath) {
		$this->ensureConnection();

		$parentFolder = $this->context->getWeb()->getFolderByServerRelativeUrl(dirname($relativeServerPath));
		$folder = $parentFolder->getFolders()->add(basename($relativeServerPath));

		$this->context->executeQuery();
		return $folder;
	}

	/**
	 * downloads a file by passing it directly into a file resource
	 *
	 * @param $relativeServerPath
	 * @param resource $fp a file resource open for writing
	 * @return \Office365\PHP\Client\Runtime\OData\ODataPayload
	 * @throws \Exception
	 */
	public function getFileViaStream($relativeServerPath, $fp) {
		if(!is_resource($fp)) {
			throw new \InvalidArgumentException('file resource expected');
		}
		$relativeServerPath = rawurlencode($relativeServerPath);
		$url = $this->context->getServiceRootUrl() .
			"web/getfilebyserverrelativeurl('$relativeServerPath')/\$value";
		$options = new RequestOptions($url);
		$options->StreamHandle = $fp;

		return $this->context->executeQueryDirect($options);
	}

	/**
	 * fetches the file content (aka download)
	 *
	 * @param $relativeServerPath
	 * @return string the file content
	 */
	public function getFile($relativeServerPath) {
		return File::openBinary($this->context, $relativeServerPath);
	}

	/**
	 * @param string $relativeServerPath
	 * @param resource $fp
	 * @param string $localPath - we need to pass the file size for the content length header
	 * @return bool
	 * @throws \Exception
	 */
	public function overwriteFileViaStream($relativeServerPath, $fp, $localPath) {
		$serverRelativeUrl = rawurlencode($relativeServerPath);
		$url = $this->context->getServiceRootUrl() . "web/getfilebyserverrelativeurl('$serverRelativeUrl')/\$value";
		$request = new RequestOptions($url);
		$request->Method = 'POST'; // yes, POST
		$request->addCustomHeader('X-HTTP-Method','PUT'); // yes, PUT
		$this->context->ensureFormDigest($request);
		//FIXME: Proper StreamHandle handling is not upstream, yet
		$request->StreamHandle = $fp;
		$request->addCustomHeader("content-length", filesize($localPath));

		return false !== $this->context->executeQueryDirect($request);
	}

	/**
	 * FIXME: use StreamHandle as in  overwriteFileViaStream for uploading a file
	 * needs to reimplement adding-file-tp-sp-logic quite some… perhaps upload an
	 * empty file and continue with overwriteFileViaStream?
	 *
	 * @param $relativeServerPath
	 * @param $content
	 * @return File
	 * @throws \Exception
	 */
	public function uploadNewFile($relativeServerPath, $content) {
		$parentFolder = $this->context->getWeb()->getFolderByServerRelativeUrl(dirname($relativeServerPath));
		$fileCollection = $parentFolder->getFiles();

		$info = new FileCreationInformation();
		$info->Content = $content;
		$info->Url = basename($relativeServerPath);
		$file = $fileCollection->add($info);
		$this->context->executeQuery();
		return $file;
	}

	/**
	 * moves a file or a folder to the given destination
	 *
	 * @param string $oldPath
	 * @param string $newPath
	 * @return bool
	 * @throws \Exception
	 */
	public function rename($oldPath, $newPath) {
		$this->ensureConnection();

		$item = $this->fetchFileOrFolder($oldPath);
		if($item instanceof File) {
			$this->renameFile($item, $newPath);
		} else if($item instanceof Folder) {
			$this->renameFolder($item, $newPath);
		} else {
			return false;
		}
		return true;
	}

	/**
	 * renames a folder
	 *
	 * @param Folder $folder
	 * @param string $newPath
	 */
	private function renameFolder(Folder $folder, $newPath) {
		$folder->rename(basename($newPath));
		$this->context->executeQuery();
	}

	/**
	 * moves a file
	 *
	 * @param File $file
	 * @param string $newPath
	 */
	private function renameFile(File $file, $newPath) {
		$newPath = rawurlencode($newPath);
		$file->moveTo($newPath, 0);
		$this->context->executeQuery();
		#$req = $this->debugGetLastRequest();
	}

	private function debugGetLastRequest() {
		$requestHistory = Requests::getHistory();
		$request = array_pop($requestHistory);
		return $request;
	}

	public function delete(ClientObject $item) {
		$this->ensureConnection();
		if ($item instanceof File) {
			$this->deleteFile($item);
		} else if ($item instanceof Folder) {
			$this->deleteFolder($item);
		}
	}

	/**
	 * deletes the given file on SP
	 *
	 * @param File $file
	 * @throws \Exception
	 */
	public function deleteFile(File $file) {
		$file->recycle();
		$this->context->executeQuery();
	}

	public function deleteFolder(Folder $folder) {
		$folder->deleteObject();
		$this->context->executeQuery();
	}

	/**
	 * @param $relativeServerPath
	 * @param null $properties
	 * @param Folder $folder
	 * @return ClientObjectCollection[]
	 */
	public function fetchFolderContents($relativeServerPath, $properties = null, Folder $folder = null) {
		$this->ensureConnection();
		if($folder === null) {
			$folder = $this->context->getWeb()->getFolderByServerRelativeUrl($relativeServerPath);
		}

		$folderCollection = $folder->getFolders();
		$fileCollection = $folder->getFiles();
		$this->context->load($folderCollection, $properties);
		$this->context->load($fileCollection, $properties);
		$this->context->executeQuery();

		$collections = ['folders' => $folderCollection, 'files' => $fileCollection];

		return $collections;
	}

	public function isHidden(ClientObject $file) {
		// ClientObject itself does not have getListItemAllFields but is
		// the common denominator of File and Folder
		if(!$file instanceof File && !$file instanceof Folder) {
			throw new \InvalidArgumentException('File or Folder expected');
		}
		if($file instanceof File) {
			// it's expensive, we only check folders
			return false;
		}
		$fields = $file->getListItemAllFields();
		if($fields->getProperties() === []) {
			$this->loadAndExecute($fields, ['Id', 'Hidden']);
		}
		$id = $fields->getProperty('Id');
		$hidden = $fields->getProperty('Hidden'); // TODO: get someone to test this in SP 2013
		if($hidden === false || $id !== null) {
			// avoids listing hidden "Forms" folder (and its contents).
			// Have not found a different mechanism to detect whether
			// a file or folder is hidden. There used to be a Hidden
			// field, but seems to have gone (since SP 2016?).
			return false;
		}
		return true;
	}

	public function loadAndExecute(ClientObject $object, array $properties = null) {
		$this->context->load($object, $properties);
		$this->context->executeQuery();
	}

	/**
	 * @return SPList
	 * @throws NotFoundException
	 */
	private function getDocumentLibrary() {
		if(!is_null($this->documentLibrary)) {
			return $this->documentLibrary;
		}

		$lists = $this->context->getWeb()->getLists()->filter('Title eq \'' . $this->documentLibraryTitle . '\'')->top(1);
		$this->context->load($lists)->executeQuery();
		if ($lists->getCount() === 1 && $lists->getData()[0] instanceof SPList) {
			$this->documentLibrary = $lists->getData()[0];
			return $this->documentLibrary;
		}

		throw new NotFoundException('List not found');
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

		if(!is_string($this->credentials['user']) || empty($this->credentials['user'])) {
			throw new \InvalidArgumentException('No user given');
		}
		if(!is_string($this->credentials['password']) || empty($this->credentials['password'])) {
			throw new \InvalidArgumentException('No password given');
		}
		$this->authContext = $this->contextsFactory->getAuthContext($this->credentials['user'], $this->credentials['password']);
		$this->authContext->AuthType = CURLAUTH_NTLM;		# Basic auth does not work somehow…
		$this->context = $this->contextsFactory->getClientContext($this->sharePointUrl, $this->authContext);
		# Auth is not triggered yet. This will happen when something is requested from SharePoint (on demand), e.g.:
	}
	
}
