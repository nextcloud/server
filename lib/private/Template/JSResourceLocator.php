<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class JSResourceLocator extends ResourceLocator {
	public function __construct(
		LoggerInterface $logger,
		IConfig $config,
		protected JSCombiner $jsCombiner,
		protected IAppManager $appManager,
	) {
		parent::__construct($logger, $config);
	}

	public function doFind(string $resource): void {
		$theme_dir = 'themes/' . $this->theme . '/';

		// Extracting the appId and the script file name
		[$app] = explode('/', $resource, 2);
		$scriptName = basename($resource);
		// Get the app root path
		$appRoot = $this->serverroot . '/apps/';
		$appWebRoot = null;
		try {
			// We need the dir name as getAppPath appends the appid
			$appRoot = dirname($this->appManager->getAppPath($app));
			// Only do this if $app_path is set, because an empty argument to realpath gets turned into cwd.
			if ($appRoot) {
				// Handle symlinks
				$appRoot = realpath($appRoot);
			}
			// Get the app webroot
			$appWebRoot = dirname($this->appManager->getAppWebPath($app));
		} catch (AppPathNotFoundException $e) {
			// ignore
		}

		if (str_contains($resource, '/l10n/')) {
			// For language files we try to load them all, so themes can overwrite
			// single l10n strings without having to translate all of them.
			$found = $this->appendScriptIfExist($this->serverroot, 'core/' . $resource)
				|| $this->appendScriptIfExist($this->serverroot, $theme_dir . 'core/' . $resource)
				|| $this->appendScriptIfExist($this->serverroot, $resource)
				|| $this->appendScriptIfExist($this->serverroot, $theme_dir . $resource)
				|| $this->appendScriptIfExist($appRoot, $resource, $appWebRoot)
				|| $this->appendScriptIfExist($this->serverroot, $theme_dir . 'apps/' . $resource);

			if ($found) {
				return;
			}
		} elseif ($this->appendScriptIfExist($this->serverroot, $theme_dir . 'apps/' . $resource)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir . $resource)
			|| $this->appendScriptIfExist($this->serverroot, $resource)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir . "dist/$app-$scriptName")
			|| $this->appendScriptIfExist($this->serverroot, "dist/$app-$scriptName")
			|| $this->appendScriptIfExist($appRoot, $resource, $appWebRoot)
			|| $this->cacheAndAppendCombineJsonIfExist($this->serverroot, $resource . '.json')
			|| $this->cacheAndAppendCombineJsonIfExist($appRoot, $resource . '.json', $app)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir . 'core/' . $resource)
			|| $this->appendScriptIfExist($this->serverroot, 'core/' . $resource)
			|| $this->appendScriptIfExist($this->serverroot, $theme_dir . "dist/core-$scriptName")
			|| $this->appendScriptIfExist($this->serverroot, "dist/core-$scriptName")
			|| $this->cacheAndAppendCombineJsonIfExist($this->serverroot, 'core/' . $resource . '.json')
		) {
			return;
		}

		// missing translations files will be ignored
		if (str_contains($resource, '/l10n/')) {
			return;
		}

		$this->logger->error('Could not find resource {resource} to load', [
			'resource' => $resource . '.js',
			'app' => 'jsresourceloader',
		]);
	}

	public function doFindTheme(string $resource): void {
	}

	/**
	 * Try to find ES6 script file (`.mjs`) with fallback to plain javascript (`.js`)
	 * @see appendIfExist()
	 */
	protected function appendScriptIfExist(string $root, string $file, ?string $webRoot = null): bool {
		if (!$this->appendIfExist($root, $file . '.mjs', $webRoot)) {
			return $this->appendIfExist($root, $file . '.js', $webRoot);
		}
		return true;
	}

	protected function cacheAndAppendCombineJsonIfExist(string $root, string $file, string $app = 'core'): bool {
		if (is_file($root . '/' . $file)) {
			if ($this->jsCombiner->process($root, $file, $app)) {
				$this->append($this->serverroot, $this->jsCombiner->getCachedJS($app, $file), null, false);
			} else {
				// Add all the files from the json
				$files = $this->jsCombiner->getContent($root, $file);
				$app_url = null;
				try {
					$app_url = $this->appManager->getAppWebPath($app);
				} catch (AppPathNotFoundException) {
					// pass
				}

				foreach ($files as $jsFile) {
					$this->append($root, $jsFile, $app_url);
				}
			}
			return true;
		}

		return false;
	}
}
