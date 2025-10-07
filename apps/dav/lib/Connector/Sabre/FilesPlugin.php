<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\AppFramework\Http\Request;
use OC\FilesMetadata\Model\FilesMetadata;
use OC\User\NoUserException;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\Files_Sharing\External\Mount as SharingExternalMount;
use OCP\Accounts\IAccountManager;
use OCP\Constants;
use OCP\Files\ForbiddenException;
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidPathException;
use OCP\Files\Storage\ISharedStorage;
use OCP\Files\StorageNotAvailableException;
use OCP\FilesMetadata\Exceptions\FilesMetadataException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\IConfig;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IFile;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class FilesPlugin extends ServerPlugin {
	// namespace
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const FILEID_PROPERTYNAME = '{http://owncloud.org/ns}id';
	public const INTERNAL_FILEID_PROPERTYNAME = '{http://owncloud.org/ns}fileid';
	public const PERMISSIONS_PROPERTYNAME = '{http://owncloud.org/ns}permissions';
	public const SHARE_PERMISSIONS_PROPERTYNAME = '{http://open-collaboration-services.org/ns}share-permissions';
	public const OCM_SHARE_PERMISSIONS_PROPERTYNAME = '{http://open-cloud-mesh.org/ns}share-permissions';
	public const SHARE_ATTRIBUTES_PROPERTYNAME = '{http://nextcloud.org/ns}share-attributes';
	public const DOWNLOADURL_PROPERTYNAME = '{http://owncloud.org/ns}downloadURL';
	public const SIZE_PROPERTYNAME = '{http://owncloud.org/ns}size';
	public const GETETAG_PROPERTYNAME = '{DAV:}getetag';
	public const LASTMODIFIED_PROPERTYNAME = '{DAV:}lastmodified';
	public const CREATIONDATE_PROPERTYNAME = '{DAV:}creationdate';
	public const DISPLAYNAME_PROPERTYNAME = '{DAV:}displayname';
	public const OWNER_ID_PROPERTYNAME = '{http://owncloud.org/ns}owner-id';
	public const OWNER_DISPLAY_NAME_PROPERTYNAME = '{http://owncloud.org/ns}owner-display-name';
	public const CHECKSUMS_PROPERTYNAME = '{http://owncloud.org/ns}checksums';
	public const DATA_FINGERPRINT_PROPERTYNAME = '{http://owncloud.org/ns}data-fingerprint';
	public const HAS_PREVIEW_PROPERTYNAME = '{http://nextcloud.org/ns}has-preview';
	public const MOUNT_TYPE_PROPERTYNAME = '{http://nextcloud.org/ns}mount-type';
	public const MOUNT_ROOT_PROPERTYNAME = '{http://nextcloud.org/ns}is-mount-root';
	public const IS_FEDERATED_PROPERTYNAME = '{http://nextcloud.org/ns}is-federated';
	public const METADATA_ETAG_PROPERTYNAME = '{http://nextcloud.org/ns}metadata_etag';
	public const UPLOAD_TIME_PROPERTYNAME = '{http://nextcloud.org/ns}upload_time';
	public const CREATION_TIME_PROPERTYNAME = '{http://nextcloud.org/ns}creation_time';
	public const SHARE_NOTE = '{http://nextcloud.org/ns}note';
	public const SHARE_HIDE_DOWNLOAD_PROPERTYNAME = '{http://nextcloud.org/ns}hide-download';
	public const SUBFOLDER_COUNT_PROPERTYNAME = '{http://nextcloud.org/ns}contained-folder-count';
	public const SUBFILE_COUNT_PROPERTYNAME = '{http://nextcloud.org/ns}contained-file-count';
	public const FILE_METADATA_PREFIX = '{http://nextcloud.org/ns}metadata-';
	public const HIDDEN_PROPERTYNAME = '{http://nextcloud.org/ns}hidden';

	/** Reference to main server object */
	private ?Server $server = null;

	/**
	 * @param Tree $tree
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param IPreview $previewManager
	 * @param IUserSession $userSession
	 * @param bool $isPublic Whether this is public WebDAV. If true, some returned information will be stripped off.
	 * @param bool $downloadAttachment
	 * @return void
	 */
	public function __construct(
		private Tree $tree,
		private IConfig $config,
		private IRequest $request,
		private IPreview $previewManager,
		private IUserSession $userSession,
		private IFilenameValidator $validator,
		private IAccountManager $accountManager,
		private bool $isPublic = false,
		private bool $downloadAttachment = true,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @return void
	 */
	public function initialize(Server $server) {
		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		$server->xml->namespaceMap[self::NS_NEXTCLOUD] = 'nc';
		$server->protectedProperties[] = self::FILEID_PROPERTYNAME;
		$server->protectedProperties[] = self::INTERNAL_FILEID_PROPERTYNAME;
		$server->protectedProperties[] = self::PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::SHARE_PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::OCM_SHARE_PERMISSIONS_PROPERTYNAME;
		$server->protectedProperties[] = self::SHARE_ATTRIBUTES_PROPERTYNAME;
		$server->protectedProperties[] = self::SIZE_PROPERTYNAME;
		$server->protectedProperties[] = self::DOWNLOADURL_PROPERTYNAME;
		$server->protectedProperties[] = self::OWNER_ID_PROPERTYNAME;
		$server->protectedProperties[] = self::OWNER_DISPLAY_NAME_PROPERTYNAME;
		$server->protectedProperties[] = self::CHECKSUMS_PROPERTYNAME;
		$server->protectedProperties[] = self::DATA_FINGERPRINT_PROPERTYNAME;
		$server->protectedProperties[] = self::HAS_PREVIEW_PROPERTYNAME;
		$server->protectedProperties[] = self::MOUNT_TYPE_PROPERTYNAME;
		$server->protectedProperties[] = self::IS_FEDERATED_PROPERTYNAME;
		$server->protectedProperties[] = self::SHARE_NOTE;

		// normally these cannot be changed (RFC4918), but we want them modifiable through PROPPATCH
		$allowedProperties = ['{DAV:}getetag'];
		$server->protectedProperties = array_diff($server->protectedProperties, $allowedProperties);

		$this->server = $server;
		$this->server->on('propFind', [$this, 'handleGetProperties']);
		$this->server->on('propPatch', [$this, 'handleUpdateProperties']);
		$this->server->on('afterBind', [$this, 'sendFileIdHeader']);
		$this->server->on('afterWriteContent', [$this, 'sendFileIdHeader']);
		$this->server->on('afterMethod:GET', [$this,'httpGet']);
		$this->server->on('afterMethod:GET', [$this, 'handleDownloadToken']);
		$this->server->on('afterResponse', function ($request, ResponseInterface $response): void {
			$body = $response->getBody();
			if (is_resource($body)) {
				fclose($body);
			}
		});
		$this->server->on('beforeMove', [$this, 'checkMove']);
		$this->server->on('beforeCopy', [$this, 'checkCopy']);
	}

	/**
	 * Plugin that checks if a copy can actually be performed.
	 *
	 * @param string $source source path
	 * @param string $target target path
	 * @throws NotFound If the source does not exist
	 * @throws InvalidPath If the target is invalid
	 */
	public function checkCopy($source, $target): void {
		$sourceNode = $this->tree->getNodeForPath($source);
		if (!$sourceNode instanceof Node) {
			return;
		}

		// Ensure source exists
		$sourceNodeFileInfo = $sourceNode->getFileInfo();
		if ($sourceNodeFileInfo === null) {
			throw new NotFound($source . ' does not exist');
		}
		// Ensure the target name is valid
		try {
			[$targetPath, $targetName] = \Sabre\Uri\split($target);
			$this->validator->validateFilename($targetName);
		} catch (InvalidPathException $e) {
			throw new InvalidPath($e->getMessage(), false);
		}
		// Ensure the target path is valid
		$segments = array_slice(explode('/', $targetPath), 2);
		foreach ($segments as $segment) {
			if ($this->validator->isFilenameValid($segment) === false) {
				$l = \OCP\Server::get(IFactory::class)->get('dav');
				throw new InvalidPath($l->t('Invalid target path'));
			}
		}
	}

	/**
	 * Plugin that checks if a move can actually be performed.
	 *
	 * @param string $source source path
	 * @param string $target target path
	 * @throws Forbidden If the source is not deletable
	 * @throws NotFound If the source does not exist
	 * @throws InvalidPath If the target name is invalid
	 */
	public function checkMove(string $source, string $target): void {
		$sourceNode = $this->tree->getNodeForPath($source);
		if (!$sourceNode instanceof Node) {
			return;
		}

		// First check copyable (move only needs additional delete permission)
		$this->checkCopy($source, $target);

		// The source needs to be deletable for moving
		$sourceNodeFileInfo = $sourceNode->getFileInfo();
		if (!$sourceNodeFileInfo->isDeletable()) {
			throw new Forbidden($source . ' cannot be deleted');
		}

		// The source is not allowed to be the parent of the target
		if (str_starts_with($source, $target . '/')) {
			throw new Forbidden($source . ' cannot be moved to it\'s parent');
		}
	}

	/**
	 * This sets a cookie to be able to recognize the start of the download
	 * the content must not be longer than 32 characters and must only contain
	 * alphanumeric characters
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function handleDownloadToken(RequestInterface $request, ResponseInterface $response) {
		$queryParams = $request->getQueryParameters();

		/**
		 * this sets a cookie to be able to recognize the start of the download
		 * the content must not be longer than 32 characters and must only contain
		 * alphanumeric characters
		 */
		if (isset($queryParams['downloadStartSecret'])) {
			$token = $queryParams['downloadStartSecret'];
			if (!isset($token[32])
				&& preg_match('!^[a-zA-Z0-9]+$!', $token) === 1) {
				// FIXME: use $response->setHeader() instead
				setcookie('ocDownloadStarted', $token, time() + 20, '/');
			}
		}
	}

	/**
	 * Add headers to file download
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response) {
		// Only handle valid files
		$node = $this->tree->getNodeForPath($request->getPath());
		if (!($node instanceof IFile)) {
			return;
		}

		// adds a 'Content-Disposition: attachment' header in case no disposition
		// header has been set before
		if ($this->downloadAttachment
			&& $response->getHeader('Content-Disposition') === null) {
			$filename = $node->getName();
			if ($this->request->isUserAgent(
				[
					Request::USER_AGENT_IE,
					Request::USER_AGENT_ANDROID_MOBILE_CHROME,
					Request::USER_AGENT_FREEBOX,
				])) {
				$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($filename) . '"');
			} else {
				$response->addHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
													 . '; filename="' . rawurlencode($filename) . '"');
			}
		}

		if ($node instanceof File) {
			//Add OC-Checksum header
			$checksum = $node->getChecksum();
			if ($checksum !== null && $checksum !== '') {
				$response->addHeader('OC-Checksum', $checksum);
			}
		}
		$response->addHeader('X-Accel-Buffering', 'no');
	}

	/**
	 * Adds all ownCloud-specific properties
	 *
	 * @param PropFind $propFind
	 * @param \Sabre\DAV\INode $node
	 * @return void
	 */
	public function handleGetProperties(PropFind $propFind, \Sabre\DAV\INode $node) {
		$httpRequest = $this->server->httpRequest;

		if ($node instanceof Node) {
			/**
			 * This was disabled, because it made dir listing throw an exception,
			 * so users were unable to navigate into folders where one subitem
			 * is blocked by the files_accesscontrol app, see:
			 * https://github.com/nextcloud/files_accesscontrol/issues/65
			 * if (!$node->getFileInfo()->isReadable()) {
			 *     // avoid detecting files through this means
			 *     throw new NotFound();
			 * }
			 */

			$propFind->handle(self::FILEID_PROPERTYNAME, function () use ($node) {
				return $node->getFileId();
			});

			$propFind->handle(self::INTERNAL_FILEID_PROPERTYNAME, function () use ($node) {
				return $node->getInternalFileId();
			});

			$propFind->handle(self::PERMISSIONS_PROPERTYNAME, function () use ($node) {
				$perms = $node->getDavPermissions();
				if ($this->isPublic) {
					// remove mount information
					$perms = str_replace(['S', 'M'], '', $perms);
				}
				return $perms;
			});

			$propFind->handle(self::SHARE_PERMISSIONS_PROPERTYNAME, function () use ($node, $httpRequest) {
				$user = $this->userSession->getUser();
				if ($user === null) {
					return null;
				}
				return $node->getSharePermissions(
					$user->getUID()
				);
			});

			$propFind->handle(self::OCM_SHARE_PERMISSIONS_PROPERTYNAME, function () use ($node, $httpRequest): ?string {
				$user = $this->userSession->getUser();
				if ($user === null) {
					return null;
				}
				$ncPermissions = $node->getSharePermissions(
					$user->getUID()
				);
				$ocmPermissions = $this->ncPermissions2ocmPermissions($ncPermissions);
				return json_encode($ocmPermissions, JSON_THROW_ON_ERROR);
			});

			$propFind->handle(self::SHARE_ATTRIBUTES_PROPERTYNAME, function () use ($node, $httpRequest) {
				return json_encode($node->getShareAttributes(), JSON_THROW_ON_ERROR);
			});

			$propFind->handle(self::GETETAG_PROPERTYNAME, function () use ($node): string {
				return $node->getETag();
			});

			$propFind->handle(self::OWNER_ID_PROPERTYNAME, function () use ($node): ?string {
				$owner = $node->getOwner();
				if (!$owner) {
					return null;
				} else {
					return $owner->getUID();
				}
			});
			$propFind->handle(self::OWNER_DISPLAY_NAME_PROPERTYNAME, function () use ($node): ?string {
				$owner = $node->getOwner();
				if (!$owner) {
					return null;
				}

				// Get current user to see if we're in a public share or not
				$user = $this->userSession->getUser();

				// If the user is logged in, we can return the display name
				if ($user !== null) {
					return $owner->getDisplayName();
				}

				// Check if the user published their display name
				try {
					$ownerAccount = $this->accountManager->getAccount($owner);
				} catch (NoUserException) {
					// do not lock process if owner is not local
					return null;
				}

				$ownerNameProperty = $ownerAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);

				// Since we are not logged in, we need to have at least the published scope
				if ($ownerNameProperty->getScope() === IAccountManager::SCOPE_PUBLISHED) {
					return $owner->getDisplayName();
				}

				return null;
			});

			$propFind->handle(self::HAS_PREVIEW_PROPERTYNAME, function () use ($node) {
				return json_encode($this->previewManager->isAvailable($node->getFileInfo()), JSON_THROW_ON_ERROR);
			});
			$propFind->handle(self::SIZE_PROPERTYNAME, function () use ($node): int|float {
				return $node->getSize();
			});
			$propFind->handle(self::MOUNT_TYPE_PROPERTYNAME, function () use ($node) {
				return $node->getFileInfo()->getMountPoint()->getMountType();
			});

			/**
			 * This is a special property which is used to determine if a node
			 * is a mount root or not, e.g. a shared folder.
			 * If so, then the node can only be unshared and not deleted.
			 * @see https://github.com/nextcloud/server/blob/cc75294eb6b16b916a342e69998935f89222619d/lib/private/Files/View.php#L696-L698
			 */
			$propFind->handle(self::MOUNT_ROOT_PROPERTYNAME, function () use ($node) {
				return $node->getNode()->getInternalPath() === '' ? 'true' : 'false';
			});

			$propFind->handle(self::SHARE_NOTE, function () use ($node): ?string {
				$user = $this->userSession->getUser();
				return $node->getNoteFromShare(
					$user?->getUID()
				);
			});

			$propFind->handle(self::SHARE_HIDE_DOWNLOAD_PROPERTYNAME, function () use ($node) {
				$storage = $node->getNode()->getStorage();
				if ($storage->instanceOfStorage(ISharedStorage::class)) {
					/** @var ISharedStorage $storage */
					return match($storage->getShare()->getHideDownload()) {
						true => 'true',
						false => 'false',
					};
				} else {
					return null;
				}
			});

			$propFind->handle(self::DATA_FINGERPRINT_PROPERTYNAME, function () {
				return $this->config->getSystemValue('data-fingerprint', '');
			});
			$propFind->handle(self::CREATIONDATE_PROPERTYNAME, function () use ($node) {
				return (new \DateTimeImmutable())
					->setTimestamp($node->getFileInfo()->getCreationTime())
					->format(\DateTimeInterface::ATOM);
			});
			$propFind->handle(self::CREATION_TIME_PROPERTYNAME, function () use ($node) {
				return $node->getFileInfo()->getCreationTime();
			});

			foreach ($node->getFileInfo()->getMetadata() as $metadataKey => $metadataValue) {
				$propFind->handle(self::FILE_METADATA_PREFIX . $metadataKey, $metadataValue);
			}

			$propFind->handle(self::HIDDEN_PROPERTYNAME, function () use ($node) {
				$isLivePhoto = isset($node->getFileInfo()->getMetadata()['files-live-photo']);
				$isMovFile = $node->getFileInfo()->getMimetype() === 'video/quicktime';
				return ($isLivePhoto && $isMovFile) ? 'true' : 'false';
			});

			/**
			 * Return file/folder name as displayname. The primary reason to
			 * implement it this way is to avoid costly fallback to
			 * CustomPropertiesBackend (esp. visible when querying all files
			 * in a folder).
			 */
			$propFind->handle(self::DISPLAYNAME_PROPERTYNAME, function () use ($node) {
				return $node->getName();
			});

			$propFind->handle(self::IS_FEDERATED_PROPERTYNAME, function () use ($node) {
				return $node->getFileInfo()->getMountPoint()
					instanceof SharingExternalMount;
			});
		}

		if ($node instanceof File) {
			$propFind->handle(self::DOWNLOADURL_PROPERTYNAME, function () use ($node) {
				try {
					$directDownloadUrl = $node->getDirectDownload();
					if (isset($directDownloadUrl['url'])) {
						return $directDownloadUrl['url'];
					}
				} catch (StorageNotAvailableException $e) {
					return false;
				} catch (ForbiddenException $e) {
					return false;
				}
				return false;
			});

			$propFind->handle(self::CHECKSUMS_PROPERTYNAME, function () use ($node) {
				$checksum = $node->getChecksum();
				if ($checksum === null || $checksum === '') {
					return null;
				}

				return new ChecksumList($checksum);
			});

			$propFind->handle(self::UPLOAD_TIME_PROPERTYNAME, function () use ($node) {
				return $node->getFileInfo()->getUploadTime();
			});
		}

		if ($node instanceof Directory) {
			$propFind->handle(self::SIZE_PROPERTYNAME, function () use ($node) {
				return $node->getSize();
			});

			$requestProperties = $propFind->getRequestedProperties();

			if (in_array(self::SUBFILE_COUNT_PROPERTYNAME, $requestProperties, true)
				|| in_array(self::SUBFOLDER_COUNT_PROPERTYNAME, $requestProperties, true)) {
				$nbFiles = 0;
				$nbFolders = 0;
				foreach ($node->getChildren() as $child) {
					if ($child instanceof File) {
						$nbFiles++;
					} elseif ($child instanceof Directory) {
						$nbFolders++;
					}
				}

				$propFind->handle(self::SUBFILE_COUNT_PROPERTYNAME, $nbFiles);
				$propFind->handle(self::SUBFOLDER_COUNT_PROPERTYNAME, $nbFolders);
			}
		}
	}

	/**
	 * translate Nextcloud permissions to OCM Permissions
	 *
	 * @param $ncPermissions
	 * @return array
	 */
	protected function ncPermissions2ocmPermissions($ncPermissions) {
		$ocmPermissions = [];

		if ($ncPermissions & Constants::PERMISSION_SHARE) {
			$ocmPermissions[] = 'share';
		}

		if ($ncPermissions & Constants::PERMISSION_READ) {
			$ocmPermissions[] = 'read';
		}

		if (($ncPermissions & Constants::PERMISSION_CREATE)
			|| ($ncPermissions & Constants::PERMISSION_UPDATE)) {
			$ocmPermissions[] = 'write';
		}

		return $ocmPermissions;
	}

	/**
	 * Update ownCloud-specific properties
	 *
	 * @param string $path
	 * @param PropPatch $propPatch
	 *
	 * @return void
	 */
	public function handleUpdateProperties($path, PropPatch $propPatch) {
		$node = $this->tree->getNodeForPath($path);
		if (!($node instanceof Node)) {
			return;
		}

		$propPatch->handle(self::LASTMODIFIED_PROPERTYNAME, function ($time) use ($node) {
			if (empty($time)) {
				return false;
			}
			$node->touch($time);
			return true;
		});
		$propPatch->handle(self::GETETAG_PROPERTYNAME, function ($etag) use ($node) {
			if (empty($etag)) {
				return false;
			}
			return $node->setEtag($etag) !== -1;
		});
		$propPatch->handle(self::CREATIONDATE_PROPERTYNAME, function ($time) use ($node) {
			if (empty($time)) {
				return false;
			}
			$dateTime = new \DateTimeImmutable($time);
			$node->setCreationTime($dateTime->getTimestamp());
			return true;
		});
		$propPatch->handle(self::CREATION_TIME_PROPERTYNAME, function ($time) use ($node) {
			if (empty($time)) {
				return false;
			}
			$node->setCreationTime((int)$time);
			return true;
		});

		$this->handleUpdatePropertiesMetadata($propPatch, $node);

		/**
		 * Disable modification of the displayname property for files and
		 * folders via PROPPATCH. See PROPFIND for more information.
		 */
		$propPatch->handle(self::DISPLAYNAME_PROPERTYNAME, function ($displayName) {
			return 403;
		});
	}


	/**
	 * handle the update of metadata from PROPPATCH requests
	 *
	 * @param PropPatch $propPatch
	 * @param Node $node
	 *
	 * @throws FilesMetadataException
	 */
	private function handleUpdatePropertiesMetadata(PropPatch $propPatch, Node $node): void {
		$userId = $this->userSession->getUser()?->getUID();
		if ($userId === null) {
			return;
		}

		$accessRight = $this->getMetadataFileAccessRight($node, $userId);
		$filesMetadataManager = $this->initFilesMetadataManager();
		$knownMetadata = $filesMetadataManager->getKnownMetadata();

		foreach ($propPatch->getRemainingMutations() as $mutation) {
			if (!str_starts_with($mutation, self::FILE_METADATA_PREFIX)) {
				continue;
			}

			$propPatch->handle(
				$mutation,
				function (mixed $value) use ($accessRight, $knownMetadata, $node, $mutation, $filesMetadataManager): bool {
					/** @var FilesMetadata $metadata */
					$metadata = $filesMetadataManager->getMetadata((int)$node->getFileId(), true);
					$metadata->setStorageId($node->getNode()->getStorage()->getCache()->getNumericStorageId());
					$metadataKey = substr($mutation, strlen(self::FILE_METADATA_PREFIX));

					// confirm metadata key is editable via PROPPATCH
					if ($knownMetadata->getEditPermission($metadataKey) < $accessRight) {
						throw new FilesMetadataException('you do not have enough rights to update \'' . $metadataKey . '\' on this node');
					}

					if ($value === null) {
						$metadata->unset($metadataKey);
						$filesMetadataManager->saveMetadata($metadata);
						return true;
					}

					// If the metadata is unknown, it defaults to string.
					try {
						$type = $knownMetadata->getType($metadataKey);
					} catch (FilesMetadataNotFoundException) {
						$type = IMetadataValueWrapper::TYPE_STRING;
					}

					switch ($type) {
						case IMetadataValueWrapper::TYPE_STRING:
							$metadata->setString($metadataKey, $value, $knownMetadata->isIndex($metadataKey));
							break;
						case IMetadataValueWrapper::TYPE_INT:
							$metadata->setInt($metadataKey, $value, $knownMetadata->isIndex($metadataKey));
							break;
						case IMetadataValueWrapper::TYPE_FLOAT:
							$metadata->setFloat($metadataKey, $value);
							break;
						case IMetadataValueWrapper::TYPE_BOOL:
							$metadata->setBool($metadataKey, $value, $knownMetadata->isIndex($metadataKey));
							break;
						case IMetadataValueWrapper::TYPE_ARRAY:
							$metadata->setArray($metadataKey, $value);
							break;
						case IMetadataValueWrapper::TYPE_STRING_LIST:
							$metadata->setStringList($metadataKey, $value, $knownMetadata->isIndex($metadataKey));
							break;
						case IMetadataValueWrapper::TYPE_INT_LIST:
							$metadata->setIntList($metadataKey, $value, $knownMetadata->isIndex($metadataKey));
							break;
					}

					$filesMetadataManager->saveMetadata($metadata);

					return true;
				}
			);
		}
	}

	/**
	 * init default internal metadata
	 *
	 * @return IFilesMetadataManager
	 */
	private function initFilesMetadataManager(): IFilesMetadataManager {
		/** @var IFilesMetadataManager $manager */
		$manager = \OCP\Server::get(IFilesMetadataManager::class);
		$manager->initMetadata('files-live-photo', IMetadataValueWrapper::TYPE_STRING, false, IMetadataValueWrapper::EDIT_REQ_WRITE_PERMISSION);

		return $manager;
	}

	/**
	 * based on owner and shares, returns the bottom limit to update related metadata
	 *
	 * @param Node $node
	 * @param string $userId
	 *
	 * @return int
	 */
	private function getMetadataFileAccessRight(Node $node, string $userId): int {
		if ($node->getOwner()?->getUID() === $userId) {
			return IMetadataValueWrapper::EDIT_REQ_OWNERSHIP;
		} else {
			$filePermissions = $node->getSharePermissions($userId);
			if ($filePermissions & Constants::PERMISSION_UPDATE) {
				return IMetadataValueWrapper::EDIT_REQ_WRITE_PERMISSION;
			}
		}

		return IMetadataValueWrapper::EDIT_REQ_READ_PERMISSION;
	}

	/**
	 * @param string $filePath
	 * @param ?\Sabre\DAV\INode $node
	 * @return void
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function sendFileIdHeader($filePath, ?\Sabre\DAV\INode $node = null) {
		// we get the node for the given $filePath here because in case of afterCreateFile $node is the parent folder
		try {
			$node = $this->server->tree->getNodeForPath($filePath);
			if ($node instanceof Node) {
				$fileId = $node->getFileId();
				if (!is_null($fileId)) {
					$this->server->httpResponse->setHeader('OC-FileId', $fileId);
				}
			}
		} catch (NotFound) {
		}
	}
}
