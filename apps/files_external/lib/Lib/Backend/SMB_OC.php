<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\SessionCredentials;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;
use OCP\IUser;

/**
 * Deprecated SMB_OC class - use SMB with the password::sessioncredentials auth mechanism
 */
class SMB_OC extends Backend {
	use LegacyDependencyCheckPolyfill;

	public function __construct(IL10N $l, SessionCredentials $legacyAuth, SMB $smbBackend) {
		$this
			->setIdentifier('\OC\Files\Storage\SMB_OC')
			->setStorageClass('\OCA\Files_External\Lib\Storage\SMB')
			->setText($l->t('SMB/CIFS using OC login'))
			->addParameters([
				new DefinitionParameter('host', $l->t('Host')),
				(new DefinitionParameter('username_as_share', $l->t('Login as share')))
					->setType(DefinitionParameter::VALUE_BOOLEAN),
				(new DefinitionParameter('share', $l->t('Share')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->setPriority(BackendService::PRIORITY_DEFAULT - 10)
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->setLegacyAuthMechanism($legacyAuth)
			->deprecateTo($smbBackend)
		;
	}

	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null) {
		$username_as_share = ($storage->getBackendOption('username_as_share') === true);

		if ($username_as_share) {
			$share = '/' . $storage->getBackendOption('user');
			$storage->setBackendOption('share', $share);
		}
	}
}
