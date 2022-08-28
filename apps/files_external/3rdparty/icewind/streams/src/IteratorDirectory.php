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
class IteratorDirectory extends WrapperHandler implements Directory {
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
	 * @throws \BadMethodCallException
	 */
	protected function loadContext($name = null) {
		$context = parent::loadContext($name);
		if (isset($context['iterator'])) {
			$this->iterator = $context['iterator'];
		} elseif (isset($context['array'])) {
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
		$this->loadContext();
		return true;
	}

	/**
	 * @return string|bool
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
	 * @return resource|false
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source) {
		if ($source instanceof \Iterator) {
			$options = [
				'iterator' => $source
			];
		} elseif (is_array($source)) {
			$options = [
				'array' => $source
			];
		} else {
			throw new \BadMethodCallException('$source should be an Iterator or array');
		}
		return self::wrapSource(self::NO_SOURCE_DIR, $options);
	}
}
