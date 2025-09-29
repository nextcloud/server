<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCP\IL10N;

class DAV extends Backend {
	use LegacyDependencyCheckPolyfill;

	public function __construct(IL10N $l, Password $legacyAuth) {
		$this
			->setIdentifier('dav')
			->addIdentifierAlias('\OC\Files\Storage\DAV') // legacy compat
			->setStorageClass('\OC\Files\Storage\DAV')
			->setText($l->t('WebDAV'))
			->addParameters([
				new DefinitionParameter('host', $l->t('URL')),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('secure', $l->t('Secure https://')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setDefaultValue(true),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->setLegacyAuthMechanism($legacyAuth)
		;
	}
}
