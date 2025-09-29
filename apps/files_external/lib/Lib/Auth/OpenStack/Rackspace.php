<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\OpenStack;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * Rackspace authentication
 */
class Rackspace extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('openstack::rackspace')
			->setScheme(self::SCHEME_OPENSTACK)
			->setText($l->t('Rackspace'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				(new DefinitionParameter('key', $l->t('API key')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
			])
		;
	}
}
