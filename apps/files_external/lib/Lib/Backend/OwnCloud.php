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

class OwnCloud extends Backend {
	public function __construct(IL10N $l, Password $legacyAuth) {
		$this
			->setIdentifier('owncloud')
			->addIdentifierAlias('\OC\Files\Storage\OwnCloud') // legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\OwnCloud')
			->setText($l->t('Nextcloud'))
			->addParameters([
				new DefinitionParameter('host', $l->t('URL')),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('secure', $l->t('Secure https://')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setDefaultValue(true),
				(new DefinitionParameter('password', $l->t('Password')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL)
					->setType(DefinitionParameter::VALUE_PASSWORD),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->setLegacyAuthMechanism($legacyAuth)
		;
	}
}
