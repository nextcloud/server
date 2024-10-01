<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

class HubBundle extends Bundle {
	public function getName() {
		return $this->l10n->t('Hub bundle');
	}

	public function getAppIdentifiers() {
		$hubApps = [
			'spreed',
			'contacts',
			'calendar',
			'mail',
		];

		$architecture = function_exists('php_uname') ? php_uname('m') : null;
		if (isset($architecture) && PHP_OS_FAMILY === 'Linux' && in_array($architecture, ['x86_64', 'aarch64'])) {
			$hubApps[] = 'richdocuments';
			$hubApps[] = 'richdocumentscode' . ($architecture === 'aarch64' ? '_arm64' : '');
		}

		return $hubApps;
	}
}
