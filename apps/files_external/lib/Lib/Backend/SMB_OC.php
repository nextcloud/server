<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		$username_as_share = ($storage->getBackendOption('username_as_share') === true);

		if ($username_as_share) {
			$share = '/' . $storage->getBackendOption('user');
			$storage->setBackendOption('share', $share);
		}
	}
}
