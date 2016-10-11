<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams;

/**
 * Create a directory handle from an iterator or array
 *
 * The following options should be passed in the context when opening the stream
 * [
 *     'dir' => [
 *        'array'    => string[]
 *        'iterator' => \Iterator
 *     ]
 * ]
 *
 * Either 'array' or 'iterator' need to be set, if both are set, 'iterator' takes preference
 */
class IteratorDirectory implements Directory {
	/**
	 * @var resource
	 */
	public $context;

	/**
	 * @var \Iterator
	 */
	protected $iterator;

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
		if (isset($context['iterator'])) {
			$this->iterator = $context['iterator'];
		} else if (isset($context['array'])) {
			$this->iterator = new \ArrayIterator($context['array']);
		} else {
			throw new \BadMethodCallException('Invalid context, iterator or array not set');
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
		if ($this->iterator->valid()) {
			$result = $this->iterator->current();
			$this->iterator->next();
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function dir_closedir() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function dir_rewinddir() {
		$this->iterator->rewind();
		return true;
	}

	/**
	 * Creates a directory handle from the provided array or iterator
	 *
	 * @param \Iterator | array $source
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source) {
		if ($source instanceof \Iterator) {
			$context = stream_context_create(array(
				'dir' => array(
					'iterator' => $source)
			));
		} else if (is_array($source)) {
			$context = stream_context_create(array(
				'dir' => array(
					'array' => $source)
			));
		} else {
			throw new \BadMethodCallException('$source should be an Iterator or array');
		}
		stream_wrapper_register('iterator', '\Icewind\Streams\IteratorDirectory');
		$wrapped = opendir('iterator://', $context);
		stream_wrapper_unregister('iterator');
		return $wrapped;
	}
}
