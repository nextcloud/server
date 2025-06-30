<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Backend;

use OCA\Files_External\Lib\Auth\AmazonS3\AccessKey;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\LegacyDependencyCheckPolyfill;
use OCP\IL10N;

class AmazonS3 extends Backend {
	use LegacyDependencyCheckPolyfill;

	public function __construct(IL10N $l, AccessKey $legacyAuth) {
		$this
			->setIdentifier('amazons3')
			->addIdentifierAlias('\OC\Files\Storage\AmazonS3') // legacy compat
			->setStorageClass('\OCA\Files_External\Lib\Storage\AmazonS3')
			->setText($l->t('Amazon S3'))
			->addParameters([
				new DefinitionParameter('bucket', $l->t('Bucket')),
				(new DefinitionParameter('hostname', $l->t('Hostname')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('port', $l->t('Port')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('region', $l->t('Region')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('storageClass', $l->t('Storage Class')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('use_ssl', $l->t('Enable SSL')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setDefaultValue(true),
				(new DefinitionParameter('use_path_style', $l->t('Enable Path Style')))
					->setType(DefinitionParameter::VALUE_BOOLEAN),
				(new DefinitionParameter('legacy_auth', $l->t('Legacy (v2) authentication')))
					->setType(DefinitionParameter::VALUE_BOOLEAN),
				(new DefinitionParameter('useMultipartCopy', $l->t('Enable multipart copy')))
					->setType(DefinitionParameter::VALUE_BOOLEAN)
					->setDefaultValue(true),
				(new DefinitionParameter('sse_c_key', $l->t('SSE-C encryption key')))
					->setType(DefinitionParameter::VALUE_PASSWORD)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			])
			->addAuthScheme(AccessKey::SCHEME_AMAZONS3_ACCESSKEY)
			->addAuthScheme(AuthMechanism::SCHEME_NULL)
			->setLegacyAuthMechanism($legacyAuth)
		;
	}
}
