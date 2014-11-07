<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Encryption\Exceptions;

/**
 * General encryption exception
 * Possible Error Codes:
 * 10 - unexpected end of encryption header
 * 20 - unexpected blog size
 * 30 - encryption header to large
 * 40 - unknown cipher
 * 50 - encryption failed
 * 60 - no private key available
 */
class EncryptionException extends \Exception {
	const UNEXPECTED_END_OF_ENCRTYPTION_HEADER = 10;
	const UNEXPECTED_BLOG_SIZE = 20;
	const ENCRYPTION_HEADER_TO_LARGE = 30;
	const UNKNOWN_CIPHER = 40;
	const ENCRYPTION_FAILED = 50;
	const NO_PRIVATE_KEY_AVAILABLE = 60;

}

/**
 * Throw this exception if multi key encrytion fails
 *
 * Possible error codes:
 * 10 - empty plain content was given
 * 20 - openssl_seal failed
 */
class MultiKeyEncryptException extends EncryptionException {
}

/**
 * Throw this encryption if multi key decryption failed
 *
 * Possible error codes:
 * 10 - empty encrypted content was given
 * 20 - openssl_open failed
 */
class MultiKeyDecryptException extends EncryptionException {
}
