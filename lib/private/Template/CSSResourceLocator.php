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

class CSSResourceLocator extends ResourceLocator {
	public function __construct(
		LoggerInterface $logger,
		IConfig $config,
		protected IAppManager $appManager,
	) {
		parent::__construct($logger, $config);
	}

	public function doFind(string $resource): void {
		$parts = explode('/', $resource);
		if (count($parts) < 2) {
			return;
		}
		$app = $parts[0];
		$filename = $parts[array_key_last($parts)];
		if ($this->appendIfExist($this->serverroot, $resource . '.css')
			|| $this->appendIfExist($this->serverroot, 'core/' . $resource . '.css')
			|| $this->appendIfExist($this->serverroot, 'dist/' . $app . '-' . $filename . '.css')
		) {
			return;
		}
		try {
			$app_path = $this->appManager->getAppPath($app);
			$app_url = $this->appManager->getAppWebPath($app);
		} catch (AppPathNotFoundException $e) {
			$this->logger->error('Could not find resource {resource} to load', [
				'resource' => $resource . '.css',
				'app' => 'cssresourceloader',
				'exception' => $e,
			]);
			return;
		}

		// Account for the possibility of having symlinks in app path. Doing
		// this here instead of above as an empty argument to realpath gets
		// turned into cwd.
		$app_path = realpath($app_path);

		$this->append($app_path, join('/', array_slice($parts, 1)) . '.css', $app_url);
	}

	public function doFindTheme(string $resource): void {
		$theme_dir = 'themes/' . $this->theme . '/';
		$this->appendIfExist($this->serverroot, $theme_dir . 'apps/' . $resource . '.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir . $resource . '.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir . 'core/' . $resource . '.css');
	}

	public function append(string $root, string $file, ?string $webRoot = null, bool $throw = true, bool $scss = false): void {
		if (!$scss) {
			parent::append($root, $file, $webRoot, $throw);
		} else {
			if ($webRoot === null || $webRoot === '') {
				$webRoot = $this->findWebRoot($root);

				if ($webRoot === null) {
					$webRoot = '';
					$this->logger->error('ResourceLocator can not find a web root (root: {root}, file: {file}, webRoot: {webRoot}, throw: {throw})', [
						'app' => 'lib',
						'root' => $root,
						'file' => $file,
						'webRoot' => $webRoot,
						'throw' => $throw ? 'true' : 'false'
					]);

					if ($throw && $root === '/') {
						throw new ResourceNotFoundException($file, $webRoot);
					}
				}
			}

			$this->resources[] = [$webRoot ?: \OC::$WEBROOT, $webRoot, $file];
		}
	}
}
