<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

class ResourceNotFoundException extends \LogicException {
	protected $resource;
	protected $webPath;

	/**
	 * @param string $resource
	 * @param string $webPath
	 */
	public function __construct($resource, $webPath) {
		parent::__construct('Resource not found');
		$this->resource = $resource;
		$this->webPath = $webPath;
	}

	/**
	 * @return string
	 */
	public function getResourcePath() {
		return $this->resource . '/' . $this->webPath;
	}
}
