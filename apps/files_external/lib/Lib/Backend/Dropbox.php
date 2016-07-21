<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

namespace OCA\Files_External\Lib\Backend;

use \OCP\IL10N;
use \OCA\Files_External\Lib\Backend\Backend;
use \OCA\Files_External\Lib\DefinitionParameter;
use \OCA\Files_External\Lib\Auth\AuthMechanism;
use \OCA\Files_External\Service\BackendService;
use \OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;

use \OCA\Files_External\Lib\Auth\OAuth1\OAuth1;

class Dropbox extends Backend {

	use LegacyDependencyCheckPolyfill;

	public function __construct(IL10N $l, OAuth1 $legacyAuth) {
		$this
			->setIdentifier('dropbox')
			->addIdentifierAlias('\OC\Files\Storage\Dropbox') // legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\Dropbox')
			->setText($l->t('Dropbox'))
			->addParameters([
				// all parameters handled in OAuth1 mechanism
			])
			->addAuthScheme(AuthMechanism::SCHEME_OAUTH1)
			->addCustomJs('dropbox')
			->setLegacyAuthMechanism($legacyAuth)
		;
	}

}
