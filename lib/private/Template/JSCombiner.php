<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class JSCombiner {
	protected ICache $depsCache;

	public function __construct(
		protected IAppData $appData,
		protected IURLGenerator $urlGenerator,
		protected ICacheFactory $cacheFactory,
		protected IConfig $config,
		protected LoggerInterface $logger,
	) {
		$this->depsCache = $this->cacheFactory->createDistributed('JS-' . md5($this->urlGenerator->getBaseUrl()));
	}

	public function process(string $root, string $file, string $app): bool {
		if ($this->config->getSystemValueBool('debug') || !$this->config->getSystemValueBool('installed')) {
			return false;
		}

		$path = explode('/', $root . '/' . $file);

		$fileName = array_pop($path);
		$path = implode('/', $path);

		try {
			$folder = $this->appData->getFolder($app);
		} catch (NotFoundException $e) {
			// creating css appdata folder
			$folder = $this->appData->newFolder($app);
		}

		if ($this->isCached($fileName, $folder)) {
			return true;
		}
		return $this->cache($path, $fileName, $folder);
	}

	protected function isCached(string $fileName, ISimpleFolder $folder): bool {
		$fileName = str_replace('.json', '.js', $fileName);

		if (!$folder->fileExists($fileName)) {
			return false;
		}

		$fileName = $fileName . '.deps';
		try {
			$deps = $this->depsCache->get($folder->getName() . '-' . $fileName);
			$fromCache = true;
			if ($deps === null || $deps === '') {
				$fromCache = false;
				$depFile = $folder->getFile($fileName);
				$deps = $depFile->getContent();
			}

			// check again
			if ($deps === null || $deps === '') {
				$this->logger->info('JSCombiner: deps file empty: ' . $fileName);
				return false;
			}

			$deps = json_decode($deps, true);

			if ($deps === null) {
				return false;
			}

			foreach ($deps as $file => $mtime) {
				if (!file_exists($file) || filemtime($file) > $mtime) {
					return false;
				}
			}

			if ($fromCache === false) {
				$this->depsCache->set($folder->getName() . '-' . $fileName, json_encode($deps));
			}

			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	protected function cache(string $path, string $fileName, ISimpleFolder $folder): bool {
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

		$fileName = str_replace('.json', '.js', $fileName);
		try {
			$cachedfile = $folder->getFile($fileName);
		} catch (NotFoundException $e) {
			$cachedfile = $folder->newFile($fileName);
		}

		$depFileName = $fileName . '.deps';
		try {
			$depFile = $folder->getFile($depFileName);
		} catch (NotFoundException $e) {
			$depFile = $folder->newFile($depFileName);
		}

		try {
			$gzipFile = $folder->getFile($fileName . '.gzip'); # Safari doesn't like .gz
		} catch (NotFoundException $e) {
			$gzipFile = $folder->newFile($fileName . '.gzip'); # Safari doesn't like .gz
		}

		try {
			$cachedfile->putContent($res);
			$deps = json_encode($deps);
			$depFile->putContent($deps);
			$this->depsCache->set($folder->getName() . '-' . $depFileName, $deps);
			$gzipFile->putContent(gzencode($res, 9));
			$this->logger->debug('JSCombiner: successfully cached: ' . $fileName);
			return true;
		} catch (NotPermittedException|NotFoundException $e) {
			$this->logger->error('JSCombiner: unable to cache: ' . $fileName);
			return false;
		}
	}

	public function getCachedJS(string $appName, string $fileName): string {
		$tmpfileLoc = explode('/', $fileName);
		$fileName = array_pop($tmpfileLoc);
		$fileName = str_replace('.json', '.js', $fileName);

		return substr($this->urlGenerator->linkToRoute('core.Js.getJs', ['fileName' => $fileName, 'appName' => $appName]), strlen(\OC::$WEBROOT) + 1);
	}

	/**
	 * @return string[]
	 */
	public function getContent(string $root, string $file): array {
		$data = json_decode(file_get_contents($root . '/' . $file));
		if (!is_array($data)) {
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
	public function resetCache(): void {
		$this->cacheFactory->createDistributed('JS-')->clear();
		$appDirectory = $this->appData->getDirectoryListing();
		foreach ($appDirectory as $folder) {
			foreach ($folder->getDirectoryListing() as $file) {
				$file->delete();
			}
		}
	}
}
