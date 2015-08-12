<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
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

namespace OCA\Files_External\Lib\Auth;

use \OCP\IL10N;
use \OCA\Files_External\Lib\Auth\AuthMechanism;
use \OCA\Files_external\Lib\StorageConfig;

/**
 * Null authentication mechanism
 */
class NullMechanism extends AuthMechanism {

	public function __construct(IL10N $l) {
		$this
			->setScheme(self::SCHEME_NULL)
			->setText($l->t('None'))
		;
	}

}
