<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

class TemplateFileLocator {
	protected $form_factor;
	protected $dirs;
	private $path;

	/**
	 * @param string[] $dirs
	 * @param string $form_factor
	 */
	public function __construct( $form_factor, $dirs ) {
		$this->form_factor = $form_factor;
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
			$file = $dir.$template.$this->form_factor.'.php';
			if (is_file($file)) {
				$this->path = $dir;
				return $file;
			}
			$file = $dir.$template.'.php';
			if (is_file($file)) {
				$this->path = $dir;
				return $file;
			}
		}
		throw new \Exception('template file not found: template:'.$template.' formfactor:'.$this->form_factor);
	}

	public function getPath() {
		return $this->path;
	}
}
