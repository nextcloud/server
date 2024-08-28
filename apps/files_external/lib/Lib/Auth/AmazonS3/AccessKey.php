<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\AmazonS3;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCP\IL10N;

/**
 * Amazon S3 access key authentication
 */
class AccessKey extends AuthMechanism {
	public const SCHEME_AMAZONS3_ACCESSKEY = 'amazons3_accesskey';

	public function __construct(IL10N $l) {
		$this
			->setIdentifier('amazons3::accesskey')
			->setScheme(self::SCHEME_AMAZONS3_ACCESSKEY)
			->setText($l->t('Access key'))
			->addParameters([
				new DefinitionParameter('key', $l->t('Access key')),
				(new DefinitionParameter('secret', $l->t('Secret key')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
			]);
	}
}
