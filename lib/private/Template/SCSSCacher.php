<?php
/**
 * @copyright Copyright (c) 2016, John Molakvoæ (skjnldsv@protonmail.com)
 *
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Template;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ParserException;
use Leafo\ScssPhp\Formatter\Crunched;
use Leafo\ScssPhp\Formatter\Expanded;
use OC\Files\AppData\Factory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;

class SCSSCacher {

	/** @var ILogger */
	protected $logger;

	/** @var IAppData */
	protected $appData;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IConfig */
	protected $config;

	/** @var string */
	protected $serverRoot;

	/** @var ICache */
	protected $depsCache;

	/** @var null|string */
	protected $injectedVariables = null;

	/** @var ICacheFactory */
	private $cacheFactory;

	/**
	 * @param ILogger $logger
	 * @param Factory $appDataFactory
	 * @param IURLGenerator $urlGenerator
	 * @param IConfig $config
	 * @param \OC_Defaults $defaults
	 * @param string $serverRoot
	 * @param ICacheFactory $cacheFactory
	 */
	public function __construct(ILogger $logger,
								Factory $appDataFactory,
								IURLGenerator $urlGenerator,
								IConfig $config,
								\OC_Defaults $defaults,
								$serverRoot,
								ICacheFactory $cacheFactory) {
		$this->logger = $logger;
		$this->appData = $appDataFactory->get('css');
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->defaults = $defaults;
		$this->serverRoot = $serverRoot;
		$this->cacheFactory = $cacheFactory;
		$this->depsCache = $cacheFactory->createDistributed('SCSS-' . md5($this->urlGenerator->getBaseUrl()));
	}

	/**
	 * Process the caching process if needed
	 * @param string $root Root path to the nextcloud installation
	 * @param string $file
	 * @param string $app The app name
	 * @return boolean
	 */
	public function process($root, $file, $app) {
		$path = explode('/', $root . '/' . $file);

		$fileNameSCSS = array_pop($path);
		$fileNameCSS = $this->prependVersionPrefix($this->prependBaseurlPrefix(str_replace('.scss', '.css', $fileNameSCSS)), $app);

		$path = implode('/', $path);
		$webDir = $this->getWebDir($path, $app, $this->serverRoot, \OC::$WEBROOT);

		try {
			$folder = $this->appData->getFolder($app);
		} catch(NotFoundException $e) {
			// creating css appdata folder
			$folder = $this->appData->newFolder($app);
		}


		if(!$this->variablesChanged() && $this->isCached($fileNameCSS, $folder)) {
			return true;
		}
		return $this->cache($path, $fileNameCSS, $fileNameSCSS, $folder, $webDir);
	}

	/**
	 * @param $appName
	 * @param $fileName
	 * @return ISimpleFile
	 */
	public function getCachedCSS($appName, $fileName) {
		$folder = $this->appData->getFolder($appName);
		$cachedFileName = $this->prependVersionPrefix($this->prependBaseurlPrefix($fileName), $appName);
		return $folder->getFile($cachedFileName);
	}

	/**
	 * Check if the file is cached or not
	 * @param string $fileNameCSS
	 * @param ISimpleFolder $folder
	 * @return boolean
	 */
	private function isCached($fileNameCSS, ISimpleFolder $folder) {
		try {
			$cachedFile = $folder->getFile($fileNameCSS);
			if ($cachedFile->getSize() > 0) {
				$depFileName = $fileNameCSS . '.deps';
				$deps = $this->depsCache->get($folder->getName() . '-' . $depFileName);
				if ($deps === null) {
					$depFile = $folder->getFile($depFileName);
					$deps = $depFile->getContent();
					//Set to memcache for next run
					$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
				}
				$deps = json_decode($deps, true);

				foreach ($deps as $file=>$mtime) {
					if (!file_exists($file) || filemtime($file) > $mtime) {
						return false;
					}
				}
				return true;
			}
			return false;
		} catch(NotFoundException $e) {
			return false;
		}
	}

	/**
	 * Check if the variables file has changed
	 * @return bool
	 */
	private function variablesChanged() {
		$injectedVariables = $this->getInjectedVariables();
		if($this->config->getAppValue('core', 'scss.variables') !== md5($injectedVariables)) {
			$this->resetCache();
			$this->config->setAppValue('core', 'scss.variables', md5($injectedVariables));
			return true;
		}
		return false;
	}

	/**
	 * Cache the file with AppData
	 * @param string $path
	 * @param string $fileNameCSS
	 * @param string $fileNameSCSS
	 * @param ISimpleFolder $folder
	 * @param string $webDir
	 * @return boolean
	 */
	private function cache($path, $fileNameCSS, $fileNameSCSS, ISimpleFolder $folder, $webDir) {
		$scss = new Compiler();
		$scss->setImportPaths([
			$path,
			$this->serverRoot . '/core/css/',
		]);
		// Continue after throw
		$scss->setIgnoreErrors(true);
		if($this->config->getSystemValue('debug')) {
			// Debug mode
			$scss->setFormatter(Expanded::class);
			$scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
		} else {
			// Compression
			$scss->setFormatter(Crunched::class);
		}

		try {
			$cachedfile = $folder->getFile($fileNameCSS);
		} catch(NotFoundException $e) {
			$cachedfile = $folder->newFile($fileNameCSS);
		}

		$depFileName = $fileNameCSS . '.deps';
		try {
			$depFile = $folder->getFile($depFileName);
		} catch (NotFoundException $e) {
			$depFile = $folder->newFile($depFileName);
		}

		// Compile
		try {
			$compiledScss = $scss->compile(
				'@import "variables.scss";' .
				$this->getInjectedVariables() .
				'@import "'.$fileNameSCSS.'";');
		} catch(ParserException $e) {
			$this->logger->error($e, ['app' => 'core']);
			return false;
		}

		// Gzip file
		try {
			$gzipFile = $folder->getFile($fileNameCSS . '.gzip'); # Safari doesn't like .gz
		} catch (NotFoundException $e) {
			$gzipFile = $folder->newFile($fileNameCSS . '.gzip'); # Safari doesn't like .gz
		}

		try {
			$data = $this->rebaseUrls($compiledScss, $webDir);
			$cachedfile->putContent($data);
			$deps = json_encode($scss->getParsedFiles());
			$depFile->putContent($deps);
			$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
			$gzipFile->putContent(gzencode($data, 9));
			$this->logger->debug('SCSSCacher: '.$webDir.'/'.$fileNameSCSS.' compiled and successfully cached', ['app' => 'core']);
			return true;
		} catch(NotPermittedException $e) {
			$this->logger->error('SCSSCacher: unable to cache: ' . $fileNameSCSS);
			return false;
		}
	}

	/**
	 * Reset scss cache by deleting all generated css files
	 * We need to regenerate all files when variables change
	 */
	public function resetCache() {
		$this->injectedVariables = null;
		$this->cacheFactory->createDistributed('SCSS-')->clear();
		$appDirectory = $this->appData->getDirectoryListing();
		foreach ($appDirectory as $folder) {
			foreach ($folder->getDirectoryListing() as $file) {
				try {
					$file->delete();
				} catch(NotPermittedException $e) {
					$this->logger->logException($e, ['message' => 'SCSSCacher: unable to delete file: ' . $file->getName()]);
				}
			}
		}
	}

	/**
	 * @return string SCSS code for variables from OC_Defaults
	 */
	private function getInjectedVariables() {
		if ($this->injectedVariables !== null)
			return $this->injectedVariables;
		$variables = '';
		foreach ($this->defaults->getScssVariables() as $key => $value) {
			$variables .= '$' . $key . ': ' . $value . ';';
		}

		// check for valid variables / otherwise fall back to defaults
		try {
			$scss = new Compiler();
			$scss->compile($variables);
			$this->injectedVariables = $variables;
		} catch (ParserException $e) {
			$this->logger->error($e, ['app' => 'core']);
		}

		return $variables;
	}

	/**
	 * Add the correct uri prefix to make uri valid again
	 * @param string $css
	 * @param string $webDir
	 * @return string
	 */
	private function rebaseUrls($css, $webDir) {
		$re = '/url\([\'"]([^\/][\.\w?=\/-]*)[\'"]\)/x';
		$subst = 'url(\''.$webDir.'/$1\')';
		return preg_replace($re, $subst, $css);
	}

	/**
	 * Return the cached css file uri
	 * @param string $appName the app name
	 * @param string $fileName
	 * @return string
	 */
	public function getCachedSCSS($appName, $fileName) {
		$tmpfileLoc = explode('/', $fileName);
		$fileName = array_pop($tmpfileLoc);
		$fileName = $this->prependVersionPrefix($this->prependBaseurlPrefix(str_replace('.scss', '.css', $fileName)), $appName);

		return substr($this->urlGenerator->linkToRoute('core.Css.getCss', ['fileName' => $fileName, 'appName' => $appName]), strlen(\OC::$WEBROOT) + 1);
	}

	/**
	 * Prepend hashed base url to the css file
	 * @param string $cssFile
	 * @return string
	 */
	private function prependBaseurlPrefix($cssFile) {
		$frontendController = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		return substr(md5($this->urlGenerator->getBaseUrl() . $frontendController), 0, 4) . '-' . $cssFile;
	}

	/**
	 * Prepend hashed app version hash
	 * @param string $cssFile
	 * @param string $appId
	 * @return string
	 */
	private function prependVersionPrefix($cssFile, $appId) {
		$appVersion = \OC_App::getAppVersion($appId);
		if ($appVersion !== '0') {
			return substr(md5($appVersion), 0, 4) . '-' . $cssFile;
		}
		$coreVersion = \OC_Util::getVersionString();
		return substr(md5($coreVersion), 0, 4) . '-' . $cssFile;
	}

	/**
	 * Get WebDir root
	 * @param string $path the css file path
	 * @param string $appName the app name
	 * @param string $serverRoot the server root path
	 * @param string $webRoot the nextcloud installation root path
	 * @return string the webDir
	 */
	private function getWebDir($path, $appName, $serverRoot, $webRoot) {
		// Detect if path is within server root AND if path is within an app path
		if ( strpos($path, $serverRoot) === false && $appWebPath = \OC_App::getAppWebPath($appName)) {
			// Get the file path within the app directory
			$appDirectoryPath = explode($appName, $path)[1];
			// Remove the webroot
			return str_replace($webRoot, '', $appWebPath.$appDirectoryPath);
		}
		return $webRoot.substr($path, strlen($serverRoot));
	}
}
