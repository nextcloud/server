<?php
/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCA\Encryption\Exception;

/**
 * Base class for all encryption exception
 *
 * Possible Error Codes:
 * 10 - unknown error
 * 20 - unexpected end of encryption header
 * 30 - unexpected blog size
 * 40 - encryption header to large
 * 50 - unknown cipher
 * 60 - encryption failed
 * 70 - decryption failed
 * 80 - empty data
 * 90 - private key missing
 */
class EncryptionException extends \Exception {
	const UNKNOWN = 10;
	const UNEXPECTED_END_OF_ENCRYPTION_HEADER = 20;
	const UNEXPECTED_BLOG_SIZE = 30;
	const ENCRYPTION_HEADER_TO_LARGE = 40;
	const UNKNOWN_CIPHER = 50;
	const ENCRYPTION_FAILED = 60;
	const DECRYPTION_FAILED = 70;
	const EMPTY_DATA = 80;
	const PRIVATE_KEY_MISSING = 90;
}
