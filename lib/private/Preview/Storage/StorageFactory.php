<?php

namespace OC\Preview\Storage;

use OC;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Preview\Db\Preview;
use OCP\IConfig;

class StorageFactory implements IPreviewStorage {
	private ?IPreviewStorage $backend = null;

	public function __construct(private PrimaryObjectStoreConfig $objectStoreConfig, private IConfig $config) {}

	public function writePreview(Preview $preview, $stream): false|int {
		return $this->getBackend()->writePreview($preview, $stream);
	}

	public function readPreview(Preview $preview) {
		return $this->getBackend()->readPreview($preview);
	}

	public function deletePreview(Preview $preview) {
		$this->getBackend()->deletePreview($preview);
	}

	private function getBackend(): IPreviewStorage {
		if ($this->backend) {
			return $this->backend;
		}

		$objectStoreConfig = $this->objectStoreConfig->getObjectStoreConfigForRoot();

		if ($objectStoreConfig) {
			$objectStore = $this->objectStoreConfig->buildObjectStore($objectStoreConfig);
			$this->backend = new ObjectStorePreviewStorage($objectStore, $objectStoreConfig['arguments']);
		} else {
			$configDataDirectory = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
			$this->backend = new LocalPreviewStorage($configDataDirectory);
		}

		return $this->backend;
	}
}
