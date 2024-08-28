<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;
use OCP\IUser;

class Local extends Backend {
	public function __construct(IL10N $l, NullMechanism $legacyAuth) {
		$this
			->setIdentifier('local')
			->addIdentifierAlias('\OC\Files\Storage\Local') // legacy compat
			->setStorageClass('\OC\Files\Storage\Local')
			->setText($l->t('Local'))
			->addParameters([
				new DefinitionParameter('datadir', $l->t('Location')),
			])
			->setAllowedVisibility(BackendService::VISIBILITY_ADMIN)
			->setPriority(BackendService::PRIORITY_DEFAULT + 50)
			->addAuthScheme(AuthMechanism::SCHEME_NULL)
			->setLegacyAuthMechanism($legacyAuth)
		;
	}

	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null): void {
		$storage->setBackendOption('isExternal', true);
	}
}
