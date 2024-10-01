<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Type;

/**
 * @deprecated 18.0.0
 */
class TemplateManager {
	protected $templates = [];

	public function registerTemplate($mimetype, $path) {
		$this->templates[$mimetype] = $path;
	}

	/**
	 * get the path of the template for a mimetype
	 *
	 * @deprecated 18.0.0
	 * @param string $mimetype
	 * @return string|null
	 */
	public function getTemplatePath($mimetype) {
		if (isset($this->templates[$mimetype])) {
			return $this->templates[$mimetype];
		} else {
			return null;
		}
	}

	/**
	 * get the template content for a mimetype
	 *
	 * @deprecated 18.0.0
	 * @param string $mimetype
	 * @return string
	 */
	public function getTemplate($mimetype) {
		$path = $this->getTemplatePath($mimetype);
		if ($path) {
			return file_get_contents($path);
		} else {
			return '';
		}
	}
}
