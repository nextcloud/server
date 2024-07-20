<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\OpenStack;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * OpenStack Keystone authentication
 */
class OpenStackV2 extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('openstack::openstack')
			->setScheme(self::SCHEME_OPENSTACK)
			->setText($l->t('OpenStack v2'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
				new DefinitionParameter('tenant', $l->t('Tenant name')),
				new DefinitionParameter('url', $l->t('Identity endpoint URL')),
			])
		;
	}
}
