<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
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

use OC\SystemConfig;
use OCP\ICache;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICacheFactory;
use OCP\ILogger;
use OCP\IURLGenerator;

class JSCombiner {

	/** @var IAppData */
	protected $appData;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var ICache */
	protected $depsCache;

	/** @var SystemConfig */
	protected $config;

	/** @var ILogger */
	protected $logger;

	/** @var ICacheFactory */
	private $cacheFactory;

	/**
	 * @param IAppData $appData
	 * @param IURLGenerator $urlGenerator
	 * @param ICacheFactory $cacheFactory
	 * @param SystemConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IAppData $appData,
								IURLGenerator $urlGenerator,
								ICacheFactory $cacheFactory,
								SystemConfig $config,
								ILogger $logger) {
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
		$this->cacheFactory = $cacheFactory;
		$this->depsCache = $this->cacheFactory->createDistributed('JS-' . md5($this->urlGenerator->getBaseUrl()));
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param string $root
	 * @param string $file
	 * @param string $app
	 * @return bool
	 */
	public function process($root, $file, $app) {
		if ($this->config->getValue('debug') || !$this->config->getValue('installed')) {
			return false;
		}

		$path = explode('/', $root . '/' . $file);

		$fileName = array_pop($path);
		$path = implode('/', $path);

		try {
			$folder = $this->appData->getFolder($app);
		} catch(NotFoundException $e) {
			// creating css appdata folder
			$folder = $this->appData->newFolder($app);
		}

		if($this->isCached($fileName, $folder, $app)) {
			return true;
		}
		return $this->cache($path, $fileName, $folder, $app);
	}

	/**
	 * @param string $fileName
	 * @param ISimpleFolder $folder
	 * @return bool
	 */
	protected function isCached($fileName, ISimpleFolder $folder, string $app) {
		$fileName = $this->prependVersionPrefix(str_replace('.json', '.js', $fileName), $app);

		if (!$folder->fileExists($fileName)) {
			return false;
		}

		$fileName = $fileName . '.deps';
		try {
			$deps = $this->depsCache->get($folder->getName() . '-' . $fileName);
			if ($deps === null || $deps === '') {
				$depFile = $folder->getFile($fileName);
				$deps = $depFile->getContent();
			}

			// check again
			if ($deps === null || $deps === '') {
				$this->logger->info('JSCombiner: deps file empty: ' . $fileName);
				return false;
			}

			$deps = json_decode($deps, true);

			if ($deps === NULL) {
				return false;
			}

			foreach ($deps as $file=>$mtime) {
				if (!file_exists($file) || filemtime($file) > $mtime) {
					return false;
				}
			}

			return true;
		} catch(NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @param string $fileName
	 * @param ISimpleFolder $folder
	 * @return bool
	 */
	protected function cache($path, $fileName, ISimpleFolder $folder, $app) {
		$deps = [];
		$fullPath = $path . '/' . $fileName;
		$data = json_decode(file_get_contents($fullPath));
		$deps[$fullPath] = filemtime($fullPath);

		$res = '';
		foreach ($data as $file) {
			$filePath = $path . '/' . $file;

			if (is_file($filePath)) {
				$res .= file_get_contents($filePath);
				$res .= PHP_EOL . PHP_EOL;
				$deps[$filePath] = filemtime($filePath);
			}
		}

		$fileNameCached = $this->prependVersionPrefix(str_replace('.json', '.js', $fileName), $app);
		try {
			$cachedfile = $folder->getFile($fileNameCached);
		} catch(NotFoundException $e) {
			$cachedfile = $folder->newFile($fileNameCached);
		}

		$depFileName = $fileNameCached . '.deps';
		try {
			$depFile = $folder->getFile($depFileName);
		} catch (NotFoundException $e) {
			$depFile = $folder->newFile($depFileName);
		}

		try {
			$gzipFile = $folder->getFile($fileNameCached . '.gzip'); # Safari doesn't like .gz
		} catch (NotFoundException $e) {
			$gzipFile = $folder->newFile($fileNameCached . '.gzip'); # Safari doesn't like .gz
		}

		try {
			$cachedfile->putContent($res);
			$deps = json_encode($deps);
			$depFile->putContent($deps);
			$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
			$gzipFile->putContent(gzencode($res, 9));
			$this->logger->debug('JSCombiner: successfully cached: ' . $fileNameCached);
			return true;
		} catch (NotPermittedException $e) {
			$this->logger->error('JSCombiner: unable to cache: ' . $fileNameCached);
			return false;
		}
	}

	/**
	 * @param string $appName
	 * @param string $fileName
	 * @return string
	 */
	public function getCachedJS($appName, $fileName) {
		$tmpfileLoc = explode('/', $fileName);
		$fileName = array_pop($tmpfileLoc);
		$fileName = $this->prependVersionPrefix(str_replace('.json', '.js', $fileName), $appName);

		return substr($this->urlGenerator->linkToRoute('core.Js.getJs', array('fileName' => $fileName, 'appName' => $appName)), strlen(\OC::$WEBROOT) + 1);
	}

	/**
	 * @param string $root
	 * @param string $file
	 * @return string[]
	 */
	public function getContent($root, $file) {
		/** @var array $data */
		$data = json_decode(file_get_contents($root . '/' . $file));
		if(!is_array($data)) {
			return [];
		}

		$path = explode('/', $file);
		array_pop($path);
		$path = implode('/', $path);

		$result = [];
		foreach ($data as $f) {
			$result[] = $path . '/' . $f;
		}

		return $result;
	}


	/**
	 * Clear cache with combined javascript files
	 *
	 * @throws NotFoundException
	 */
	public function resetCache() {
		$this->cacheFactory->createDistributed('JS-')->clear();
		$appDirectory = $this->appData->getDirectoryListing();
		foreach ($appDirectory as $folder) {
			foreach ($folder->getDirectoryListing() as $file) {
				$file->delete();
			}
		}
	}

	/**
	 * Prepend hashed app version hash
	 * @param string $jsFile
	 * @param string $appId
	 * @return string
	 */
	private function prependVersionPrefix(string $jsFile, string $appId): string {
		$appVersion = \OC_App::getAppVersion($appId);
		if ($appVersion !== '0') {
			return substr(md5($appVersion), 0, 4) . '-' . $jsFile;
		}
		$coreVersion = \OC_Util::getVersionString();
		return substr(md5($coreVersion), 0, 4) . '-' . $jsFile;
	}
}
