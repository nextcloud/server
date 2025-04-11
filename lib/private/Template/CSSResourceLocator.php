<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Template;

use Psr\Log\LoggerInterface;

class CSSResourceLocator extends ResourceLocator {
	public function __construct(LoggerInterface $logger) {
		parent::__construct($logger);
	}

	/**
	 * @param string $style
	 */
	public function doFind($style) {
		$app = substr($style, 0, strpos($style, '/'));
		if ($this->appendIfExist($this->serverroot, $style . '.css')
			|| $this->appendIfExist($this->serverroot, 'core/' . $style . '.css')
		) {
			return;
		}
		$style = substr($style, strpos($style, '/') + 1);
		$app_path = \OC_App::getAppPath($app);
		$app_url = \OC_App::getAppWebPath($app);

		if ($app_path === false && $app_url === false) {
			$this->logger->error('Could not find resource {resource} to load', [
				'resource' => $app . '/' . $style . '.css',
				'app' => 'cssresourceloader',
			]);
			return;
		}

		// Account for the possibility of having symlinks in app path. Doing
		// this here instead of above as an empty argument to realpath gets
		// turned into cwd.
		$app_path = realpath($app_path);

		$this->append($app_path, $style . '.css', $app_url);
	}

	/**
	 * @param string $style
	 */
	public function doFindTheme($style) {
		$theme_dir = 'themes/' . $this->theme . '/';
		$this->appendIfExist($this->serverroot, $theme_dir . 'apps/' . $style . '.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir . $style . '.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir . 'core/' . $style . '.css');
	}

	public function append($root, $file, $webRoot = null, $throw = true, $scss = false) {
		if (!$scss) {
			parent::append($root, $file, $webRoot, $throw);
		} else {
			if (!$webRoot) {
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
