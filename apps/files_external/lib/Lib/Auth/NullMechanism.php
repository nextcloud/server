<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth;

use OCP\IL10N;

/**
 * Null authentication mechanism
 */
class NullMechanism extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('null::null')
			->setScheme(self::SCHEME_NULL)
			->setText($l->t('None'))
		;
	}
}
