<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib\Auth\OpenStack;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * OpenStack Keystone authentication
 */
class OpenStackV3 extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('openstack::openstackv3')
			->setScheme(self::SCHEME_OPENSTACK)
			->setText($l->t('OpenStack v3'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				new DefinitionParameter('domain', $l->t('Domain')),
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
				new DefinitionParameter('tenant', $l->t('Tenant name')),
				new DefinitionParameter('url', $l->t('Identity endpoint URL'))
			])
		;
	}
}
