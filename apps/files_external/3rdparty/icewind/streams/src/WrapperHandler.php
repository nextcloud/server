<?php
/**
 * SPDX-FileCopyrightText: 2019 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\Streams;

class WrapperHandler {
	/** @var resource $context */
	protected $context;

	const NO_SOURCE_DIR = 1;

	/**
	 * get the protocol name that is generated for the class
	 * @param string|null $class
	 * @return string
	 */
	public static function getProtocol($class = null) {
		if ($class === null) {
			$class = static::class;
		}

		$parts = explode('\\', $class);
		return strtolower(array_pop($parts));
	}

	private static function buildContext($protocol, $context, $source) {
		if (is_array($context)) {
			$context['source'] = $source;
			return stream_context_create([$protocol => $context]);
		} else {
			return $context;
		}
	}

	/**
	 * @param resource|int $source
	 * @param resource|array $context
	 * @param string|null $protocol deprecated, protocol is now automatically generated
	 * @param string|null $class deprecated, class is now automatically generated
	 * @return resource|false
	 */
	protected static function wrapSource($source, $context = [], $protocol = null, $class = null, $mode = 'r+') {
		if ($class === null) {
			$class = static::class;
		}

		if ($protocol === null) {
			$protocol = self::getProtocol($class);
		}

		$context = self::buildContext($protocol, $context, $source);
		try {
			stream_wrapper_register($protocol, $class);
			if (self::isDirectoryHandle($source)) {
				return opendir($protocol . '://', $context);
			} else {
				return fopen($protocol . '://', $mode, false, $context);
			}
		} finally {
			stream_wrapper_unregister($protocol);
		}
	}

	protected static function isDirectoryHandle($resource) {
		if ($resource === self::NO_SOURCE_DIR) {
			return true;
		}
		if (!is_resource($resource)) {
			throw new \BadMethodCallException('Invalid stream source');
		}
		$meta = stream_get_meta_data($resource);
		return $meta['stream_type'] === 'dir' || $meta['stream_type'] === 'user-space-dir';
	}

	/**
	 * Load the source from the stream context and return the context options
	 *
	 * @param string|null $name if not set, the generated protocol name is used
	 * @return array
	 * @throws \BadMethodCallException
	 */
	protected function loadContext($name = null) {
		if ($name === null) {
			$parts = explode('\\', static::class);
			$name = strtolower(array_pop($parts));
		}

		$context = stream_context_get_options($this->context);
		if (isset($context[$name])) {
			$context = $context[$name];
		} else {
			throw new \BadMethodCallException('Invalid context, "' . $name . '" options not set');
		}
		return $context;
	}
}
