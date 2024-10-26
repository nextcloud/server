<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\OpenStack\OpenStackV2;
use OCA\Files_External\Lib\Auth\OpenStack\Rackspace;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCP\IL10N;

class Swift extends Backend {
	use LegacyDependencyCheckPolyfill;

	public function __construct(IL10N $l, OpenStackV2 $openstackAuth, Rackspace $rackspaceAuth) {
		$this
			->setIdentifier('swift')
			->addIdentifierAlias('\OC\Files\Storage\Swift') // legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\Swift')
			->setText($l->t('OpenStack Object Storage'))
			->addParameters([
				(new DefinitionParameter('service_name', $l->t('Service name')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				new DefinitionParameter('region', $l->t('Region')),
				new DefinitionParameter('bucket', $l->t('Bucket')),
				(new DefinitionParameter('timeout', $l->t('Request timeout (seconds)')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->addAuthScheme(AuthMechanism::SCHEME_OPENSTACK)
			->setLegacyAuthMechanismCallback(function (array $params) use ($openstackAuth, $rackspaceAuth) {
				if (isset($params['options']['key']) && $params['options']['key']) {
					return $rackspaceAuth;
				}
				return $openstackAuth;
			})
		;
	}
}
