<?php
/**
 * Copyright (c) 2013 Thomas Müller thomas.mueller@tmit.eu
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */
namespace OC;

use OCP\IPreview;
use OCP\Preview\IProvider;

class PreviewManager implements IPreview {
	/** @var \OCP\IConfig */
	protected $config;

	/** @var array */
	protected $providers = [];

	/** @var array mime type => support status */
	protected $mimeTypeSupportMap = [];

	/** @var array */
	protected $defaultProviders;

	/**
	 * Constructor
	 *
	 * @param \OCP\IConfig $config
	 */
	public function __construct(\OCP\IConfig $config) {
		$this->config = $config;

		if ($this->config->getSystemValue('enable_previews', true)) {
			// Register the default providers like txt, image, ...
			$this->registerCoreProviders();
		}
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be
	 * called in case preview providers are actually requested
	 *
	 * $callable has to return an instance of \OCP\Preview\IProvider
	 *
	 * @param string $mimeTypeRegex Regex with the mime types that are supported by this provider
	 * @param \Closure $callable
	 * @return void
	 */
	public function registerProvider($mimeTypeRegex, \Closure $callable) {
		if (!$this->config->getSystemValue('enable_previews', true)) {
			return;
		}

		if (!isset($this->providers[$mimeTypeRegex])) {
			$this->providers[$mimeTypeRegex] = [];
		}
		$this->providers[$mimeTypeRegex][] = $callable;
	}

	/**
	 * Get all providers
	 * @return array
	 */
	public function getProviders() {
		$keys = array_map('strlen', array_keys($this->providers));
		array_multisort($keys, SORT_DESC, $this->providers);

		return $this->providers;
	}

	/**
	 * Does the manager have any providers
	 * @return bool
	 */
	public function hasProviders() {
		return !empty($this->providers);
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
	public function createPreview($file, $maxX = 100, $maxY = 75, $scaleUp = false) {
		$preview = new \OC\Preview('', '/', $file, $maxX, $maxY, $scaleUp);
		return $preview->getPreview();
	}

	/**
	 * returns true if the passed mime type is supported
	 *
	 * @param string $mimeType
	 * @return boolean
	 */
	public function isMimeSupported($mimeType = '*') {
		if (!$this->config->getSystemValue('enable_previews', true)) {
			return false;
		}

		if (isset($this->mimeTypeSupportMap[$mimeType])) {
			return $this->mimeTypeSupportMap[$mimeType];
		}

		$providerMimeTypes = array_keys($this->providers);
		foreach ($providerMimeTypes as $supportedMimeType) {
			if (preg_match($supportedMimeType, $mimeType)) {
				$this->mimeTypeSupportMap[$mimeType] = true;
				return true;
			}
		}
		$this->mimeTypeSupportMap[$mimeType] = false;
		return false;
	}

	/**
	 * Check if a preview can be generated for a file
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 */
	public function isAvailable(\OCP\Files\FileInfo $file) {
		if (!$this->config->getSystemValue('enable_previews', true)) {
			return false;
		}

		if (!$this->isMimeSupported($file->getMimetype())) {
			return false;
		}

		$mount = $file->getMountPoint();
		if ($mount and !$mount->getOption('previews', true)){
			return false;
		}

		foreach ($this->providers as $supportedMimeType => $providers) {
			if (preg_match($supportedMimeType, $file->getMimetype())) {
				foreach ($providers as $closure) {
					$provider = $closure();
					if (!($provider instanceof IProvider)) {
						continue;
					}

					/** @var $provider IProvider */
					if ($provider->isAvailable($file)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * List of enabled default providers
	 *
	 * The following providers are enabled by default:
	 *  - OC\Preview\Image
	 *  - OC\Preview\MarkDown
	 *  - OC\Preview\MP3
	 *  - OC\Preview\TXT
	 *
	 * The following providers are disabled by default due to performance or privacy concerns:
	 *  - OC\Preview\Font
	 *  - OC\Preview\Illustrator
	 *  - OC\Preview\Movie
	 *  - OC\Preview\MSOfficeDoc
	 *  - OC\Preview\MSOffice2003
	 *  - OC\Preview\MSOffice2007
	 *  - OC\Preview\OpenDocument
	 *  - OC\Preview\PDF
	 *  - OC\Preview\Photoshop
	 *  - OC\Preview\Postscript
	 *  - OC\Preview\StarOffice
	 *  - OC\Preview\SVG
	 *  - OC\Preview\TIFF
	 *
	 * @return array
	 */
	protected function getEnabledDefaultProvider() {
		if ($this->defaultProviders !== null) {
			return $this->defaultProviders;
		}

		$this->defaultProviders = $this->config->getSystemValue('enabledPreviewProviders', [
			'OC\Preview\Image',
			'OC\Preview\MarkDown',
			'OC\Preview\MP3',
			'OC\Preview\TXT',
		]);
		return $this->defaultProviders;
	}

	/**
	 * Register the default providers (if enabled)
	 *
	 * @param string $class
	 * @param string $mimeType
	 */
	protected function registerCoreProvider($class, $mimeType, $options = []) {
		if (in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
			$this->registerProvider($mimeType, function () use ($class, $options) {
				return new $class($options);
			});
		}
	}

	/**
	 * Register the default providers (if enabled)
	 */
	protected function registerCoreProviders() {
		$this->registerCoreProvider('OC\Preview\TXT', '/text\/plain/');
		$this->registerCoreProvider('OC\Preview\MarkDown', '/text\/(x-)?markdown/');
		$this->registerCoreProvider('OC\Preview\Image', '/image\/(?!tiff$)(?!svg.*).*/');
		$this->registerCoreProvider('OC\Preview\MP3', '/audio\/mpeg/');

		// SVG, Office and Bitmap require imagick
		if (extension_loaded('imagick')) {
			$checkImagick = new \Imagick();

			$imagickProviders = [
				'SVG'	=> ['mimetype' => '/image\/svg\+xml/', 'class' => '\OC\Preview\SVG'],
				'TIFF'	=> ['mimetype' => '/image\/tiff/', 'class' => '\OC\Preview\TIFF'],
				'PDF'	=> ['mimetype' => '/application\/pdf/', 'class' => '\OC\Preview\PDF'],
				'AI'	=> ['mimetype' => '/application\/illustrator/', 'class' => '\OC\Preview\Illustrator'],
				'PSD'	=> ['mimetype' => '/application\/x-photoshop/', 'class' => '\OC\Preview\Photoshop'],
				'EPS'	=> ['mimetype' => '/application\/postscript/', 'class' => '\OC\Preview\Postscript'],
				'TTF'	=> ['mimetype' => '/application\/(?:font-sfnt|x-font$)/', 'class' => '\OC\Preview\Font'],
			];

			foreach ($imagickProviders as $queryFormat => $provider) {
				$class = $provider['class'];
				if (!in_array(trim($class, '\\'), $this->getEnabledDefaultProvider())) {
					continue;
				}

				if (count($checkImagick->queryFormats($queryFormat)) === 1) {
					$this->registerCoreProvider($class, $provider['mimetype']);
				}
			}

			if (count($checkImagick->queryFormats('PDF')) === 1) {
				// Office previews are currently not supported on Windows
				if (!\OC_Util::runningOnWindows() && \OC_Helper::is_function_enabled('shell_exec')) {
					$officeFound = is_string($this->config->getSystemValue('preview_libreoffice_path', null));

					if (!$officeFound) {
						//let's see if there is libreoffice or openoffice on this machine
						$whichLibreOffice = shell_exec('command -v libreoffice');
						$officeFound = !empty($whichLibreOffice);
						if (!$officeFound) {
							$whichOpenOffice = shell_exec('command -v openoffice');
							$officeFound = !empty($whichOpenOffice);
						}
					}

					if ($officeFound) {
						$this->registerCoreProvider('\OC\Preview\MSOfficeDoc', '/application\/msword/');
						$this->registerCoreProvider('\OC\Preview\MSOffice2003', '/application\/vnd.ms-.*/');
						$this->registerCoreProvider('\OC\Preview\MSOffice2007', '/application\/vnd.openxmlformats-officedocument.*/');
						$this->registerCoreProvider('\OC\Preview\OpenDocument', '/application\/vnd.oasis.opendocument.*/');
						$this->registerCoreProvider('\OC\Preview\StarOffice', '/application\/vnd.sun.xml.*/');
					}
				}
			}
		}

		// Video requires avconv or ffmpeg and is therefor
		// currently not supported on Windows.
		if (in_array('OC\Preview\Movie', $this->getEnabledDefaultProvider()) && !\OC_Util::runningOnWindows()) {
			$avconvBinary = \OC_Helper::findBinaryPath('avconv');
			$ffmpegBinary = ($avconvBinary) ? null : \OC_Helper::findBinaryPath('ffmpeg');

			if ($avconvBinary || $ffmpegBinary) {
				// FIXME // a bit hacky but didn't want to use subclasses
				\OC\Preview\Movie::$avconvBinary = $avconvBinary;
				\OC\Preview\Movie::$ffmpegBinary = $ffmpegBinary;

				$this->registerCoreProvider('\OC\Preview\Movie', '/video\/.*/');
			}
		}
	}
}
