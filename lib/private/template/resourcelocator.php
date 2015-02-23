<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

	/**
	 * @param string $theme
	 */
	public function __construct( $theme, $core_map, $party_map ) {
		$this->theme = $theme;
		$this->mapping = $core_map + $party_map;
		$this->serverroot = key($core_map);
		$this->thirdpartyroot = key($party_map);
		$this->webroot = $this->mapping[$this->serverroot];
	}

	abstract public function doFind( $resource );
	abstract public function doFindTheme( $resource );

	public function find( $resources ) {
		try {
			foreach($resources as $resource) {
				$this->doFind($resource);
			}
			if (!empty($this->theme)) {
				foreach($resources as $resource) {
					$this->doFindTheme($resource);
				}
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage().' serverroot:'.$this->serverroot);
		}
	}

	/*
	 * append the $file resource if exist at $root
	 * @param string $root path to check
	 * @param string $file the filename
	 * @param string|null $webroot base for path, default map $root to $webroot
	 */
	protected function appendIfExist($root, $file, $webroot = null) {
		if (is_file($root.'/'.$file)) {
			if (!$webroot) {
				$webroot = $this->mapping[$root];
			}
			$this->resources[] = array($root, $webroot, $file);
			return true;
		}
		return false;
	}

	public function getResources() {
		return $this->resources;
	}
}
