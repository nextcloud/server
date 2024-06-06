<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\OAuth2;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * OAuth2 authentication
 */
class OAuth2 extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('oauth2::oauth2')
			->setScheme(self::SCHEME_OAUTH2)
			->setText($l->t('OAuth2'))
			->addParameters([
				(new DefinitionParameter('configured', 'configured'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
				new DefinitionParameter('client_id', $l->t('Client ID')),
				(new DefinitionParameter('client_secret', $l->t('Client secret')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
				(new DefinitionParameter('token', 'token'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
			])
			->addCustomJs('oauth2')
		;
	}
}
