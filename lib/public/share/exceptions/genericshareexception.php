<?php

namespace OCP\Share\Exceptions;
use OC\HintException;

/**
 * Class GenericEncryptionException
 *
 * @package OCP\Share\Exceptions
 * @since 9.0.0
 */
class GenericShareException extends HintException {

	/**
	 * @param string $message
	 * @param string $hint
	 * @param int $code
	 * @param \Exception $previous
	 * @since 9.0.0
	 */
	public function __construct($message = '', $hint = '', $code = 0, \Exception $previous = null) {
		if (empty($message)) {
			$message = 'Unspecified share exception';
		}
		parent::__construct($message, $hint, $code, $previous);
	}

}
