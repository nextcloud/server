<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_Encryption\Exception;

/**
 * Base class for all encryption exception
 *
 * Possible Error Codes:
 * 10 - generic error
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
	const GENERIC = 10;
	const UNEXPECTED_END_OF_ENCRYPTION_HEADER = 20;
	const UNEXPECTED_BLOCK_SIZE = 30;
	const ENCRYPTION_HEADER_TO_LARGE = 40;
	const UNKNOWN_CIPHER = 50;
	const ENCRYPTION_FAILED = 60;
	const DECRYPTION_FAILED = 70;
	const EMPTY_DATA = 80;
	const PRIVATE_KEY_MISSING = 90;
}
