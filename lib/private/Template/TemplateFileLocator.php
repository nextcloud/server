<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Template;

class TemplateFileLocator {
	protected $dirs;
	private $path;

	/**
	 * @param string[] $dirs
	 */
	public function __construct($dirs) {
		$this->dirs = $dirs;
	}

	/**
	 * @param string $template
	 * @return string
	 * @throws \Exception
	 */
	public function find($template) {
		if ($template === '') {
			throw new \InvalidArgumentException('Empty template name');
		}

		foreach ($this->dirs as $dir) {
			$file = $dir . $template . '.php';
			if (is_file($file)) {
				$this->path = $dir;
				return $file;
			}
		}
		throw new \Exception('template file not found: template:' . $template);
	}

	public function getPath() {
		return $this->path;
	}
}
