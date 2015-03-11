<?php
/**
 * Copyright (c) 2013 Thomas Müller thomas.mueller@tmit.eu
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */
namespace OC;

use OCP\image;
use OCP\IPreview;

class PreviewManager implements IPreview {
	/** @var array */
	protected $providers = [];

	/**
	 * In order to improve lazy loading a closure can be registered which will be
	 * called in case preview providers are actually requested
	 *
	 * $callable has to return an instance of \OC\Preview\Provider
	 *
	 * @param string $mimeTypeRegex Regex with the mime types that are supported by this provider
	 * @param \Closure $callable
	 * @return void
	 */
	public function registerProvider($mimeTypeRegex, \Closure $callable) {
		if (!isset($this->providers[$mimeTypeRegex])) {
			$this->providers[$mimeTypeRegex] = [];
		}
		$this->providers[$mimeTypeRegex][] = $callable;
	}

	/**
	 * return a preview of a file
	 *
	 * @param string $file The path to the file where you want a thumbnail from
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param boolean $scaleUp Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return \OCP\Image
	 */
	function createPreview($file, $maxX = 100, $maxY = 75, $scaleUp = false) {
		$preview = new \OC\Preview('', '/', $file, $maxX, $maxY, $scaleUp);
		return $preview->getPreview();
	}

	/**
	 * returns true if the passed mime type is supported
	 *
	 * @param string $mimeType
	 * @return boolean
	 */
	function isMimeSupported($mimeType = '*') {
		if (!\OC::$server->getConfig()->getSystemValue('enable_previews', true)) {
			return false;
		}

		$providerMimeTypes = array_keys($this->providers);
		foreach ($providerMimeTypes as $supportedMimeType) {
			if (preg_match($supportedMimeType, $mimeType)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a preview can be generated for a file
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 */
	function isAvailable($file) {
		if (!\OC::$server->getConfig()->getSystemValue('enable_previews', true)) {
			return false;
		}

		$mount = $file->getMountPoint();
		if ($mount and !$mount->getOption('previews', true)){
			return false;
		}

		foreach ($this->providers as $supportedMimeType => $providers) {
			if (preg_match($supportedMimeType, $file->getMimetype())) {
				foreach ($providers as $provider) {
					/** @var $provider \OC\Preview\Provider */
					if ($provider->isAvailable($file)) {
						return true;
					}
				}
			}
		}
		return false;
	}
}
