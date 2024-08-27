<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

class SFTP extends Backend {
	public function __construct(IL10N $l, Password $legacyAuth) {
		$this
			->setIdentifier('sftp')
			->addIdentifierAlias('\OC\Files\Storage\SFTP') // legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\SFTP')
			->setText($l->t('SFTP'))
			->addParameters([
				new DefinitionParameter('host', $l->t('Host')),
				(new DefinitionParameter('port', $l->t('Port')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('root', $l->t('Root')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->addAuthScheme(AuthMechanism::SCHEME_PUBLICKEY)
			->setLegacyAuthMechanism($legacyAuth)
		;
	}
}
