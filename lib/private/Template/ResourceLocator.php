<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

use OCP\IConfig;
use Psr\Log\LoggerInterface;

abstract class ResourceLocator {
	protected string $theme;

	protected array $mapping;
	protected string $serverroot;

	protected array $resources = [];

	public function __construct(
		protected LoggerInterface $logger,
		IConfig $config,
	) {
		$this->mapping = [
			\OC::$SERVERROOT => \OC::$WEBROOT
		];
		$this->serverroot = \OC::$SERVERROOT;

		$this->theme = $config->getSystemValueString('theme', '');

		if ($this->theme === '') {
			if (is_dir(\OC::$SERVERROOT . '/themes/default')) {
				$this->theme = 'default';
			}
		}
	}

	abstract public function doFind(string $resource): void;

	abstract public function doFindTheme(string $resource): void;

	/**
	 * Finds the resources and adds them to the list
	 */
	public function find(array $resources): void {
		foreach ($resources as $resource) {
			try {
				$this->doFind($resource);
			} catch (ResourceNotFoundException $e) {
				[$resourceApp] = explode('/', $resource, 2);
				$this->logger->debug('Could not find resource file "' . $e->getResourcePath() . '"', ['app' => $resourceApp]);
			}
		}
		if (!empty($this->theme)) {
			foreach ($resources as $resource) {
				try {
					$this->doFindTheme($resource);
				} catch (ResourceNotFoundException $e) {
					[$resourceApp] = explode('/', $resource, 2);
					$this->logger->debug('Could not find resource file in theme "' . $e->getResourcePath() . '"', ['app' => $resourceApp]);
				}
			}
		}
	}

	/**
	 * append the $file resource if exist at $root
	 *
	 * @param string $root path to check
	 * @param string $file the filename
	 * @param string|null $webRoot base for path, default map $root to $webRoot
	 * @return bool True if the resource was found, false otherwise
	 */
	protected function appendIfExist(string $root, string $file, ?string $webRoot = null): bool {
		if (is_file($root . '/' . $file)) {
			$this->append($root, $file, $webRoot, false);
			return true;
		}
		return false;
	}

	/**
	 * Attempt to find the webRoot
	 *
	 * traverse the potential web roots upwards in the path
	 *
	 * example:
	 *   - root: /srv/www/apps/myapp
	 *   - available mappings: ['/srv/www']
	 *
	 * First we check if a mapping for /srv/www/apps/myapp is available,
	 * then /srv/www/apps, /srv/www/apps, /srv/www, ... until we find a
	 * valid web root
	 *
	 * @return string|null The web root or null on failure
	 */
	protected function findWebRoot(string $root): ?string {
		$webRoot = null;
		$tmpRoot = $root;

		while ($webRoot === null) {
			if (isset($this->mapping[$tmpRoot])) {
				$webRoot = $this->mapping[$tmpRoot];
				break;
			}

			if ($tmpRoot === '/') {
				break;
			}

			$tmpRoot = dirname($tmpRoot);
		}

		if ($webRoot === null) {
			$realpath = realpath($root);

			if ($realpath && ($realpath !== $root)) {
				return $this->findWebRoot($realpath);
			}
		}

		return $webRoot;
	}

	/**
	 * append the $file resource at $root
	 *
	 * @param string $root path to check
	 * @param string $file the filename
	 * @param string|null $webRoot base for path, default map $root to $webRoot
	 * @param bool $throw Throw an exception, when the route does not exist
	 * @throws ResourceNotFoundException Only thrown when $throw is true and the resource is missing
	 */
	protected function append(string $root, string $file, ?string $webRoot = null, bool $throw = true): void {
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
			}
		}
		$this->resources[] = [$root, $webRoot, $file];

		if ($throw && !is_file($root . '/' . $file)) {
			throw new ResourceNotFoundException($file, $webRoot);
		}
	}

	/**
	 * Returns the list of all resources that should be loaded
	 */
	public function getResources(): array {
		return $this->resources;
	}
}
