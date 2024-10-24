<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use OCP\Files\ObjectStore\IObjectStore;

class Azure implements IObjectStore {
	/** @var string */
	private $containerName;
	/** @var string */
	private $accountName;
	/** @var string */
	private $accountKey;
	/** @var BlobRestProxy|null */
	private $blobClient = null;
	/** @var string|null */
	private $endpoint = null;
	/** @var bool */
	private $autoCreate = false;

	/**
	 * @param array $parameters
	 */
	public function __construct(array $parameters) {
		$this->containerName = $parameters['container'];
		$this->accountName = $parameters['account_name'];
		$this->accountKey = $parameters['account_key'];
		if (isset($parameters['endpoint'])) {
			$this->endpoint = $parameters['endpoint'];
		}
		if (isset($parameters['autocreate'])) {
			$this->autoCreate = $parameters['autocreate'];
		}
	}

	/**
	 * @return BlobRestProxy
	 */
	private function getBlobClient() {
		if (!$this->blobClient) {
			$protocol = $this->endpoint ? substr($this->endpoint, 0, strpos($this->endpoint, ':')) : 'https';
			$connectionString = 'DefaultEndpointsProtocol=' . $protocol . ';AccountName=' . $this->accountName . ';AccountKey=' . $this->accountKey;
			if ($this->endpoint) {
				$connectionString .= ';BlobEndpoint=' . $this->endpoint;
			}
			$this->blobClient = BlobRestProxy::createBlobService($connectionString);

			if ($this->autoCreate) {
				try {
					$this->blobClient->createContainer($this->containerName);
				} catch (ServiceException $e) {
					if ($e->getCode() === 409) {
						// already exists
					} else {
						throw $e;
					}
				}
			}
		}
		return $this->blobClient;
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 */
	public function getStorageId() {
		return 'azure::blob::' . $this->containerName;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	public function readObject($urn) {
		$blob = $this->getBlobClient()->getBlob($this->containerName, $urn);
		return $blob->getContentStream();
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$options = new CreateBlockBlobOptions();
		if ($mimetype) {
			$options->setContentType($mimetype);
		}
		$this->getBlobClient()->createBlockBlob($this->containerName, $urn, $stream, $options);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	public function deleteObject($urn) {
		$this->getBlobClient()->deleteBlob($this->containerName, $urn);
	}

	public function objectExists($urn) {
		try {
			$this->getBlobClient()->getBlobMetadata($this->containerName, $urn);
			return true;
		} catch (ServiceException $e) {
			if ($e->getCode() === 404) {
				return false;
			} else {
				throw $e;
			}
		}
	}

	public function copyObject($from, $to) {
		$this->getBlobClient()->copyBlob($this->containerName, $to, $this->containerName, $from);
	}
}
