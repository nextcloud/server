<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author tux-rampage <tux-rampage@users.noreply.github.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Template;

use Psr\Log\LoggerInterface;

abstract class ResourceLocator {
	protected $theme;

	protected $mapping;
	protected $serverroot;
	protected $webroot;

	protected $resources = [];

	protected LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
		$this->mapping = [
			\OC::$SERVERROOT => \OC::$WEBROOT
		];
		$this->serverroot = \OC::$SERVERROOT;
		$this->webroot = \OC::$WEBROOT;
		$this->theme = \OC_Util::getTheme();
	}

	/**
	 * @param string $resource
	 */
	abstract public function doFind($resource);

	/**
	 * @param string $resource
	 */
	abstract public function doFindTheme($resource);

	/**
	 * Finds the resources and adds them to the list
	 *
	 * @param array $resources
	 */
	public function find($resources) {
		foreach ($resources as $resource) {
			try {
				$this->doFind($resource);
			} catch (ResourceNotFoundException $e) {
				$resourceApp = substr($resource, 0, strpos($resource, '/'));
				$this->logger->debug('Could not find resource file "' . $e->getResourcePath() . '"', ['app' => $resourceApp]);
			}
		}
		if (!empty($this->theme)) {
			foreach ($resources as $resource) {
				try {
					$this->doFindTheme($resource);
				} catch (ResourceNotFoundException $e) {
					$resourceApp = substr($resource, 0, strpos($resource, '/'));
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
	protected function appendIfExist($root, $file, $webRoot = null) {
		if ($root !== false && is_file($root.'/'.$file)) {
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
	 * @param string $root
	 * @return string|null The web root or null on failure
	 */
	protected function findWebRoot($root) {
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
	protected function append($root, $file, $webRoot = null, $throw = true) {
		if (!is_string($root)) {
			if ($throw) {
				throw new ResourceNotFoundException($file, $webRoot);
			}
			return;
		}

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
			}
		}
		$this->resources[] = [$root, $webRoot, $file];

		if ($throw && !is_file($root . '/' . $file)) {
			throw new ResourceNotFoundException($file, $webRoot);
		}
	}

	/**
	 * Returns the list of all resources that should be loaded
	 * @return array
	 */
	public function getResources() {
		return $this->resources;
	}
}
