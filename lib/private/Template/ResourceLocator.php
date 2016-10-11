<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Template;

abstract class ResourceLocator {
	protected $theme;

	protected $mapping;
	protected $serverroot;
	protected $thirdpartyroot;
	protected $webroot;

	protected $resources = array();

	/** @var \OCP\ILogger */
	protected $logger;

	/**
	 * @param \OCP\ILogger $logger
	 * @param string $theme
	 * @param array $core_map
	 * @param array $party_map
	 */
	public function __construct(\OCP\ILogger $logger, $theme, $core_map, $party_map) {
		$this->logger = $logger;
		$this->theme = $theme;
		$this->mapping = $core_map + $party_map;
		$this->serverroot = key($core_map);
		$this->thirdpartyroot = key($party_map);
		$this->webroot = $this->mapping[$this->serverroot];
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
				$this->logger->error('Could not find resource file "' . $e->getResourcePath() . '"', ['app' => $resourceApp]);
			}
		}
		if (!empty($this->theme)) {
			foreach ($resources as $resource) {
				try {
					$this->doFindTheme($resource);
				} catch (ResourceNotFoundException $e) {
					$resourceApp = substr($resource, 0, strpos($resource, '/'));
					$this->logger->error('Could not find resource file "' . $e->getResourcePath() . '"', ['app' => $resourceApp]);
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
		if (is_file($root.'/'.$file)) {
			$this->append($root, $file, $webRoot, false);
			return true;
		}
		return false;
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
		if (!$webRoot) {
			$webRoot = $this->mapping[$root];
		}
		$this->resources[] = array($root, $webRoot, $file);

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
