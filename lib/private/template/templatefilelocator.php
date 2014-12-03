<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

class TemplateFileLocator {
	protected $dirs;
	private $path;

	/**
	 * @param string[] $dirs
	 */
	public function __construct( $dirs ) {
		$this->dirs = $dirs;
	}

	/**
	 * @param string $template
	 * @return string
	 * @throws \Exception
	 */
	public function find( $template ) {
		if ($template === '') {
			throw new \InvalidArgumentException('Empty template name');
		}

		foreach($this->dirs as $dir) {
			$file = $dir.$template.'.php';
			if (is_file($file)) {
				$this->path = $dir;
				return $file;
			}
		}
		throw new \Exception('template file not found: template:'.$template);
	}

	public function getPath() {
		return $this->path;
	}
}
