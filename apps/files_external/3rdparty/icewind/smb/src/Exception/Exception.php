<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Exception;

use Throwable;

/**
 * @psalm-consistent-constructor
 */
class Exception extends \Exception {
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @param string|null $path
	 * @param string|int|null $error
	 * @return Exception
	 */
	public static function unknown(?string $path, $error): Exception {
		$message = 'Unknown error (' . (string)$error . ')';
		if ($path) {
			$message .= ' for ' . $path;
		}

		return new Exception($message, is_int($error) ? $error : 0);
	}

	/**
	 * @param array<int|string, class-string<Exception>> $exceptionMap
	 * @param string|int|null $error
	 * @param string|null $path
	 * @return Exception
	 */
	public static function fromMap(array $exceptionMap, $error, ?string $path): Exception {
		if (isset($exceptionMap[$error])) {
			$exceptionClass = $exceptionMap[$error];
			if (is_numeric($error)) {
				return new $exceptionClass($path, $error);
			} else {
				return new $exceptionClass($path);
			}
		} else {
			return Exception::unknown($path, $error);
		}
	}
}
