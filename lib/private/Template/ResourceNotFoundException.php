<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
		return $this->webPath . '/' . $this->resource;
	}
}
