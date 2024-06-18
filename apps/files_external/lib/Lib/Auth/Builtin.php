<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth;

use OCP\IL10N;

/**
 * Builtin authentication mechanism, for legacy backends
 */
class Builtin extends AuthMechanism {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('builtin::builtin')
			->setScheme(self::SCHEME_BUILTIN)
			->setText($l->t('Builtin'))
		;
	}
}
