<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
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
