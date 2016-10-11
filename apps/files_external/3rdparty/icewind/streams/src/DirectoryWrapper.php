<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

class DirectoryWrapper implements Directory {
	/**
	 * @var resource
	 */
	public $context;

	/**
	 * @var resource
	 */
	protected $source;

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string $name
	 * @return array
	 * @throws \Exception
	 */
	protected function loadContext($name) {
		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		if (isset($context['source']) and is_resource($context['source'])) {
			$this->source = $context['source'];
		} else {
			throw new \BadMethodCallException('Invalid context, source not set');
		}
		return $context;
	}

	/**
	 * @param string $path
	 * @param array $options
	 * @return bool
	 */
	public function dir_opendir($path, $options) {
		$this->loadContext('dir');
		return true;
	}

	/**
	 * @return string
	 */
	public function dir_readdir() {
		return readdir($this->source);
	}

	/**
	 * @return bool
	 */
	public function dir_closedir() {
		closedir($this->source);
		return true;
	}

	/**
	 * @return bool
	 */
	public function dir_rewinddir() {
		rewinddir($this->source);
		return true;
	}

	/**
	 * @param array $options the options for the context to wrap the stream with
	 * @param string $class
	 * @return resource
	 */
	protected static function wrapWithOptions($options, $class) {
		$context = stream_context_create($options);
		stream_wrapper_register('dirwrapper', $class);
		$wrapped = opendir('dirwrapper://', $context);
		stream_wrapper_unregister('dirwrapper');
		return $wrapped;
	}
}
