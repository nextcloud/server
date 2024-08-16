<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\OAuth1;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * OAuth1 authentication
 */
class OAuth1 extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('oauth1::oauth1')
			->setScheme(self::SCHEME_OAUTH1)
			->setText($l->t('OAuth1'))
			->addParameters([
				(new DefinitionParameter('configured', 'configured'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
				new DefinitionParameter('app_key', $l->t('App key')),
				(new DefinitionParameter('app_secret', $l->t('App secret')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
				(new DefinitionParameter('token', 'token'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
				(new DefinitionParameter('token_secret', 'token_secret'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
			])
			->addCustomJs('oauth1')
		;
	}
}
